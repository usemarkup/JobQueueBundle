<?php

namespace Markup\JobQueueBundle\Repository;

use Markup\JobQueueBundle\Exception\UnknownJobLogException;
use Markup\JobQueueBundle\Model\JobLog;
use Markup\JobQueueBundle\Model\JobLogCollection;
use Markup\JobQueueBundle\Form\Data\SearchJobLogs as SearchJobLogsData;
use Markup\JobQueueBundle\Service\RecurringConsoleCommandReader;
use Predis\Client as Predis;

/**
 * Get and Set JobLogs (from redis)
 */
class JobLogRepository
{
    const REDIS_NAMESPACE = 'markup_job_queue';

    // 2 weeks
    const DEFAULT_LOG_TTL = '1209600';

    /**
     * @var Predis
     */
    private $predis;

    /**
     * @var Predis
     */
    private $tempKey;

    /**
     * @var RecurringConsoleCommandReader
     */
    private $recurringConsoleCommandReader;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var bool
     */
    private $shouldClearLogForCompleteJob;

    /**
     * @param Predis $predis
     * @param RecurringConsoleCommandReader $recurringConsoleCommandReader
     * @param int|null $ttl
     */
    public function __construct(
        Predis $predis,
        RecurringConsoleCommandReader $recurringConsoleCommandReader,
        $ttl = null
    ) {
        $this->predis = $predis;
        $this->recurringConsoleCommandReader = $recurringConsoleCommandReader;
        $this->tempKey = null;
        $this->ttl = $ttl ?: self::DEFAULT_LOG_TTL;
        $this->shouldClearLogForCompleteJob = false;
    }

    /**
     * @param string $command
     * @param string|null $uuid
     * @param string|null $topic
     * @return JobLog
     */
    public function createAndSaveJobLog($command, $uuid = null, $topic = null)
    {
        $log = new JobLog($command, $uuid, $topic);
        $this->save($log, $initial = true);
        return $log;
    }

    /**
     *
     * @param JobLog $jobLog
     * @param boolean $false If set to true will add the job to secondary indexes
     */
    public function save(JobLog $jobLog, $initial = false)
    {
        $compressed = $jobLog->toCompressedArray();

        // this key identifies the job in all indexes
        $hashKey = $this->getJobLogKey($jobLog->getUuid());

        if ($this->shouldClearLogForCompleteJob && $jobLog->getStatus() === JobLog::STATUS_COMPLETE) {
            $this->predis->del($hashKey);
            return;
        }

        // add/update canonical record
        $this->predis->hmset($hashKey, $compressed);
        $this->predis->expire($hashKey, $this->ttl);

        // update the 'status' index
        $this->addJobToStatusIndex($hashKey, $jobLog->getStatus());

        if(!$initial) {
            return;
        }

        // add to 'added' range index
        $this->addJobToTimeAddedIndex($hashKey, $compressed['added']);

        // and also to command index which stores every log for a given command
        $this->addJobToCommandKeyIndex($hashKey, $jobLog->getCommand());
    }

    /**
     * @param $hashKey
     * @param $added
     */
    private function addJobToTimeAddedIndex($hashKey, $added)
    {
        $indexName = $this->getJobAddedKey();
        $data = [$hashKey => $added];
        $this->predis->zadd($indexName, $data);
    }

    /**
     * @param $hashKey
     */
    private function removeJobFromTimeAddedIndex($hashKey)
    {
        $indexName = $this->getJobAddedKey();
        $this->predis->zrem($indexName, $hashKey);
    }

    /**
     * @param $hashKey
     * @param $command
     */
    private function addJobToCommandKeyIndex($hashKey, $command)
    {
        // Only add to the command key index if the command is within the recurring jobs library
        // it only needs to be looked up for recurring console commands, so no point in creating sets for all other commands
        if(!in_array($command, $this->recurringConsoleCommandReader->getConfigurationCommands())) {
            return;
        }
        $indexName = $this->getCommandKey($command);
        $this->predis->sadd($indexName, $hashKey);
    }

    /**
     * @param $hashKey
     */
    private function removeJobFromCommandKeyIndexes($hashKey)
    {
        // iterates known commands from recurring job config in order to get list of known commands for which to expire
        foreach($this->recurringConsoleCommandReader->getConfigurationCommands() as $command) {
            $indexName = $this->getCommandKey($command);
            $this->predis->srem($indexName, $hashKey);
        }
    }

    /**
     * Adds the job to one status index and removes it from all others
     * @param $hashKey
     * @param $jobStatus
     */
    private function addJobToStatusIndex($hashKey, $jobStatus)
    {
        foreach([JobLog::STATUS_ADDED, JobLog::STATUS_RUNNING, JobLog::STATUS_FAILED, JobLog::STATUS_COMPLETE] as $status) {
            if ($status === $jobStatus) {
                $this->predis->sadd($this->getStatusKey($status), $hashKey);
            } else {
                $this->predis->srem($this->getStatusKey($status), $hashKey);
            }
        }
    }

    /**
     * @param $hashKey
     */
    private function removeJobFromStatusIndexes($hashKey)
    {
        foreach([JobLog::STATUS_ADDED, JobLog::STATUS_RUNNING, JobLog::STATUS_FAILED, JobLog::STATUS_COMPLETE] as $status) {
            $this->predis->srem($this->getStatusKey($status), $hashKey);
        }
    }

    /**
     * Removes all jobs older than ($this->ttl - 86400 seconds) from all secondary indexes
     */
    public function removeExpiredJobsFromSecondaryIndexes()
    {
        $interval = new \DateInterval(sprintf('PT%sS', $this->ttl-86400));
        $before = (new \DateTime('now'))->sub($interval)->format('U');

        // get all old jobs
        $expiredJobs = $this->predis->zrevrangebyscore($this->getJobAddedKey(), $before, '-inf');
        if (!$expiredJobs) {
            return;
        }
        foreach($expiredJobs as $jobLogKey) {
            $this->removeJobFromTimeAddedIndex($jobLogKey);
            $this->removeJobFromCommandKeyIndexes($jobLogKey);
            $this->removeJobFromStatusIndexes($jobLogKey);
        }
    }

    /**
     * Save the fact that a job failed
     *
     * @param        $uuid
     * @param string $output
     * @param string $exitCode
     */
    public function saveFailure($uuid, $output = '', $exitCode = null)
    {
        try {
            $log = $this->getJobLog($uuid);
            $log->setStatus(JobLog::STATUS_FAILED);
            if (!$log->getCompleted()) {
                $log->setCompleted((new \DateTime('now'))->format('U'));
            }
            $log->setOutput($output);
            if ($exitCode) {
                $log->setExitCode($exitCode);
            }
            $this->save($log);
        } catch (UnknownJobLogException $e){
            // forgive exceptions
        }

    }

    /**
     * Save the output from a job
     *
     * @param        $uuid
     */
    public function saveOutput($uuid, $output = '')
    {
      try {
          $log = $this->getJobLog($uuid);
          $log->setOutput($output);
          $this->save($log);
      } catch (UnknownJobLogException $e){
          // forgive exceptions
      }
    }


    /**
     * Gets a key to identity this job uniquely in redis
     *
     * @param $uuid string job Log UUID
     * @return string
     */
    private function getJobLogKey($uuid)
    {
       return sprintf('%s:job:%s', self::REDIS_NAMESPACE, $uuid);
    }

    /**
     * Gets a temporary key used to construct a temporary intersection/union for a later search
     *
     * @param $uuid string job Log UUID
     * @return string
     */
    private function getTempKey()
    {
        if ($this->tempKey) {
            return $this->tempKey;
        }
        $tmp = sprintf('%s:temp_key_%', self::REDIS_NAMESPACE, (new \DateTime('now'))->format('U'));
        $this->tempKey = $tmp;
        return $tmp;
    }

    /**
     * Gets a key for use in the `command` index
     *
     * @param $command
     */
    private function getCommandKey($command)
    {
        $commandHash = hash('SHA256', $command);
        return sprintf('%s:command:%s', self::REDIS_NAMESPACE, $commandHash);
    }

    /**
     * Gets a key for use in the `status` index
     *
     * @param $command
     */
    private function getStatusKey($status)
    {
        return sprintf('%s:status:%s', self::REDIS_NAMESPACE, $status);
    }

    /**
     * Gets a key for use in the `added` index
     *
     * @param $command
     */
    private function getJobAddedKey()
    {
        return sprintf('%s:job_added', self::REDIS_NAMESPACE);
    }

    /**
     * @param $id
     * @return JobLog
     */
    public function getJobLog($uuid)
    {
        return $this->getJobLogByKey($this->getJobLogKey($uuid));
    }

    /**
     * @param $id
     * @return boolean
     */
    public function hasJobLog($uuid)
    {
        try {
            $this->getJobLogByKey($this->getJobLogKey($uuid));
            return true;
        } catch(UnknownJobLogException $e) {
            return false;
        }
    }

    /**
     * @param $key string
     * @throws UnknownJobLogException
     * @returns JobLog
     */
    private function getJobLogByKey($key)
    {
        $result = $this->predis->hgetall($key);
        if (!$result) {
            throw new UnknownJobLogException(sprintf('Job with key: `%s` cannot be found', $key));
        }
        return JobLog::fromCompressedArray($result);
    }

    /**
     * Gets JobLog entries discriminated using various options, can also return counts only for use in pagination
     *
     * @param SearchJobLogsData $options
     * @param int               $quantity
     * @param bool              $countOnly
     *
     * @return JobLogCollection | int
     */
    public function getJobLogs(
        SearchJobLogsData $options,
        $quantity = 10,
        $countOnly = false
    ) {

        $since = $options->getSince() ? $options->getSince()->format('U') : '-inf';
        $before = $options->getBefore() ?$options->getBefore()->format('U') : '+inf';

        if ($options->isDiscriminatorSearch()) {
            // because searching two indexes - need to make a temporary intersection of both
            $discriminator = $options->getStatus() ? $this->getStatusKey($options->getStatus()) : $this->getCommandKey($options->getCommand());
            $this->predis->zinterstore($this->getTempKey(), [$discriminator, $this->getJobAddedKey()], ['AGGREGATE MAX']);
        }

        if ($countOnly) {
            if ($options->isIdSearch()) {
                $result = $this->hasJobLog($options->getId()) ? 1 : 0;
            } else if ($options->isDiscriminatorSearch()) {
                $result = $this->predis->zcount($this->getTempKey(), $since, '+inf');
            } else {
                $result = $this->predis->zcount($this->getJobAddedKey(), $since, $before);
            }
        } else {
            $result = new JobLogCollection();
            $rangeOptions['limit'] = [
                'offset' => $options->getPageOffset() == 0 ? 0 : $options->getPageOffset()*intval($quantity),
                'count' => intval($quantity)
            ];
            $matchingJobs = null;

            if ($options->isIdSearch()) {
                if ($this->hasJobLog($options->getId())) {
                    $matchingJobs = [$this->getJobLogKey($options->getId())];
                }
            } else if ($options->isDiscriminatorSearch()) {
                $matchingJobs = $this->predis->zrevrangebyscore($this->getTempKey(), $before, $since, $rangeOptions);
            } else {
                $matchingJobs = $this->predis->zrevrangebyscore($this->getJobAddedKey(), $before, $since, $rangeOptions);
            }

            if ($matchingJobs) {
                // Iterate the matches and fetch them individually
                foreach($matchingJobs as $jobLogKey) {
                    try {
                        $result->add($this->getJobLogByKey($jobLogKey));
                    } catch (UnknownJobLogException $e) {
                        continue;
                    }
                }
            }
        }

        //unset the temporary key if it exists
        if ($this->tempKey) {
            $this->predis->del($this->getTempKey());
        }

        return $result;
    }

    /**
     * @param int $ttl
     */
    public function setTtl($ttl = null)
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * @param bool $shouldClearLogForCompleteJob
     */
    public function setShouldClearLogForCompleteJob($shouldClearLogForCompleteJob)
    {
        $this->shouldClearLogForCompleteJob = $shouldClearLogForCompleteJob;

        return $this;
    }
}
