<?php

namespace Markup\JobQueueBundle\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Markup\JobQueueBundle\Entity\JobLog;
use Markup\JobQueueBundle\Model\JobLogCollection;
use Markup\JobQueueBundle\Form\Data\SearchJobLogs as SearchJobLogsData;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Get and Set JobLogs (from redis)
 */
class JobLogRepository
{
    // 2 weeks
    const DEFAULT_LOG_TTL = '1209600';

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var bool
     */
    private $shouldClearLogForCompleteJob;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @param int|null $ttl
     */
    public function __construct(
        ManagerRegistry $doctrine,
        $ttl = null
    ) {
        $this->ttl = $ttl ?: self::DEFAULT_LOG_TTL;
        $this->shouldClearLogForCompleteJob = false;
        $this->doctrine = $doctrine;
    }

    public function createAndSaveJobLog(string $command, ?string $uuid = null, ?string $topic = null): JobLog
    {
        $log = new JobLog($command, $uuid, $topic);
        $this->save($log);
        return $log;
    }

    public function save(JobLog $jobLog): void
    {
        $existingJobLog = $this->findJobLog($jobLog->getUuid());
        if ($existingJobLog) {
            if ($this->shouldClearLogForCompleteJob) {
                if ($jobLog->getStatus() === JobLog::STATUS_COMPLETE) {
                    $this->deleteJob($existingJobLog);
                }
                return;
            }

            // update status of existing job
            $existingJobLog->setStatus($jobLog->getStatus());

            $this->update($existingJobLog);
            return;
        }

        // if no existing job, store new job

        $this->add($jobLog);
    }

    public function saveFailure(string $uuid, string $output = '', ?int $exitCode = null): void
    {
        $log = $this->findJobLog($uuid);
        if (!$log) {
            return ;
        }
        $log->setStatus(JobLog::STATUS_FAILED);
        if (!$log->getCompleted()) {
            $log->setCompleted(new \DateTime());
        }
        $log->setOutput($output);
        if ($exitCode) {
            $log->setExitCode($exitCode);
        }
        $this->save($log);
    }

    public function saveOutput(string $uuid, string $output = ''): void
    {
          $log = $this->findJobLog($uuid);
          if (!$log) {
              return;
          }
          $log->setOutput($output);
          $this->save($log);
    }

    public function getJobLogs(SearchJobLogsData $data, $maxPerPage = 10, $currentPage = 1): Pagerfanta
    {
        $query = $this->getJobLogQuery($data);

        $jobLogs = new Pagerfanta(
            new DoctrineORMAdapter($query, true, false)
        );

        $jobLogs->setMaxPerPage($maxPerPage);
        $jobLogs->setCurrentPage($currentPage);

        return $jobLogs;
    }

    public function getJobLogCollection(SearchJobLogsData $data, $limit = 10): JobLogCollection
    {
        $query = $this->getJobLogQuery($data, $limit);

        $collection = new JobLogCollection();

        foreach ($query->getResult() as $row) {
            $collection->add($row);
        }

        return $collection;
    }

    private function getJobLogQuery(SearchJobLogsData $data, ?int $limit = null): Query
    {
        $qb = $this->getEntityRepository()->createQueryBuilder('j');
        if ($data->getId()) {
            $qb->where($qb->expr()->like('j.uuid', ':uuid'))
                ->setParameter(':uuid', $data->getId());
        }

        if ($data->getSince()) {
            $qb->andWhere($qb->expr()->gte('j.added', ':after'))
                ->setParameter(':after', $data->getSince());
        }

        if ($data->getBefore()) {
            $qb->andWhere($qb->expr()->lte('j.added', ':before'))
                ->setParameter(':before', $data->getBefore());
        }

        if ($data->getStatus()) {
            $qb->andWhere($qb->expr()->eq('j.status', ':status'))
                ->setParameter(':status', $data->getStatus());
        }

        if ($data->getCommand()) {
            $qb->andWhere($qb->expr()->like('j.command', ':command'))
                ->setParameter(':command', $data->getCommand().'%');
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        $qb->orderBy('j.added', 'DESC');

        return $qb->getQuery();
    }

    public function setTtl(?int $ttl = null): void
    {
        $this->ttl = $ttl;
    }

    public function setShouldClearLogForCompleteJob(bool $shouldClearLogForCompleteJob): void
    {
        $this->shouldClearLogForCompleteJob = $shouldClearLogForCompleteJob;
    }

    private function getEntityRepository(): EntityRepository
    {
        $repository = $this->doctrine->getRepository(JobLog::class);

        if ($repository instanceof EntityRepository) {
            return $repository;
        }

        throw new \RuntimeException(sprintf('Doctrine returned an invalid repository for entity JobLog'));
    }

    private function getEntityManager(): EntityManager
    {
        $manager = $this->doctrine->getManager();
        if ($manager instanceof EntityManager) {
            return $manager;
        }

        throw new \RuntimeException(sprintf('Doctrine returned an invalid type for entity manager'));
    }

    public function findJobLog(string $uuid): ?JobLog
    {
        $jobLog = $this->getEntityRepository()->findOneBy(['uuid' => $uuid]);
        if ($jobLog instanceof JobLog) {
            return $jobLog;
        }

        return null;
    }

    public function deleteJob(JobLog $jobLog): void
    {
        $em = $this->getEntityManager();
        $em->remove($jobLog);
        $em->flush($jobLog);
    }

    public function add(JobLog $jobLog): void
    {
        $em = $this->getEntityManager();
        $em->persist($jobLog);
        $em->flush($jobLog);
    }

    public function update(JobLog $jobLog): void
    {
        $em = $this->getEntityManager();
        $em->flush($jobLog);
    }

    /**
     * Removes all jobs older than ($this->ttl - 86400 seconds) from all secondary indexes
     */
    public function removeExpiredJobs(): void
    {
        $interval = new \DateInterval(sprintf('PT%sS', $this->ttl - 86400));
        $before = (new \DateTime('now'))->sub($interval)->format('U');

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(JobLog::class, 'j')
            ->where($qb->expr()->lte('j.added', ':before'))
            ->setParameter(':before', $before);

        $qb->getQuery()->execute();
    }
}
