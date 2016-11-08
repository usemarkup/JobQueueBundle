<?php

namespace Markup\JobQueueBundle\Model;

use Symfony\Component\Process\Process;

/**
 * Stores information about a job which has been added to the queue
 */
class JobLog
{
    const STATUS_ADDED = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_FAILED = 'failed';
    const STATUS_COMPLETE = 'complete';
    const TOPIC_UNDEFINED = 'undefined';

    /**
     * @var string
     */
    private $command;

    /**
     * @var string
     */
    private $topic;

    /**
     * @var \DateTime
     */
    private $added;

    /**
     * @var \DateTime|null
     */
    private $started;

    /**
     * @var \DateTime|null
     */
    private $completed;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $output;

    /**
     * @var int|null
     */
    private $peakMemoryUse;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string|null
     */
    private $exitCode;

    /**
     * @param string $command
     * @param string $uuid
     * @param string $topic
     * @param \DateTime $added
     * @param string $status
     * @param string $output
     * @param string|null $exitCode
     * @param int|null $peakMemoryUse
     * @param \DateTime|null $completed
     */
    public function __construct(
        $command,
        $uuid = null,
        $topic = self::TOPIC_UNDEFINED,
        $added = null,
        $status = self::STATUS_ADDED,
        $output = '',
        $exitCode = null,
        $peakMemoryUse = null,
        $completed = null,
        $started = null
    ) {
        $this->command = $command;
        $this->topic = $topic;
        if ($added instanceof \DateTime) {
            $added = $added->format('U');
        }
        if (!$added) {
            $added = (new \DateTime('now'))->format('U');
        }
        $this->added = $added;
        $this->completed = $completed;
        $this->started = $started;
        $this->status = $status;
        $this->output = $output;
        $this->peakMemoryUse = $peakMemoryUse;
        $this->exitCode = $exitCode;

        // none of these values are mutable so are suitable to generate uuid
        // the 'added' makes it unique
        if (!$uuid) {
            $uuid = $this->generateUuid($command, $topic, $added);
        }
        $this->uuid = $uuid;
    }

    /**
     * Constructs from data as it is returned from redis
     * The inverse of toCompressedArray
     *
     * @param $array
     * @return JobLog
     */
    public static function fromCompressedArray(
        array $array
    ) {
        return new self(
            $array['command'],
            $array['uuid'],
            $array['topic'],
            $array['added'],
            $array['status'],
            gzuncompress($array['output']),
            $array['exitCode'],
            $array['peakMemoryUse'],
            $array['completed'],
            $array['started']
        );
    }

    /**
     * Converts this object into an array suitable for storing in redis
     *
     * @return array
     */
    public function toCompressedArray()
    {
        return [
            'command' => $this->getCommand(),
            'uuid' => $this->getUuid(),
            'topic' => $this->getTopic(),
            'added' => $this->getAdded(),
            'status' => $this->getStatus(),
            'output' => gzcompress($this->getOutput()),
            'exitCode' => $this->getExitCode(),
            'peakMemoryUse' => $this->getPeakMemoryUse(),
            'completed' => $this->getCompleted(),
            'started' => $this->getStarted(),
        ];
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @return string
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @return string
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * The duration of the job in seconds
     *
     * @return integer
     */
    public function getDuration()
    {
        if (!$this->getCompleted() || !$this->getStarted()) {
            return 0;
        }
        $duration = $this->getCompleted() - $this->getStarted();
        if ($duration === 0) {
            $duration = 1;
        }
        return $duration;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return int
     */
    public function getPeakMemoryUse()
    {
        return $this->peakMemoryUse;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * @param $command
     * @param $topic
     * @param string $added
     *
     * @return string
     */
    private function generateUuid($command, $topic, $added)
    {
        return hash('SHA256', $command.$topic.$added);
    }

    /**
     * @param string|null $completed
     * @return JobLog
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;
        return $this;
    }

    /**
     * @param string $status
     * @return JobLog
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param string $output
     * @return JobLog
     */
    public function setOutput($output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @param int|null $peakMemoryUse
     * @return JobLog
     */
    public function setPeakMemoryUse($peakMemoryUse)
    {
        $this->peakMemoryUse = $peakMemoryUse;
        return $this;
    }

    /**
     * @param null|string $exitCode
     * @return JobLog
     */
    public function setExitCode($exitCode)
    {
        $this->exitCode = $exitCode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * @param string|null $started
     * @return JobLog
     */
    public function setStarted($started)
    {
        $this->started = $started;
        return $this;
    }

    /**
     * @return string
     */
    public function getExitCodeText()
    {
        if (!$this->getExitCode()) {
            return '';
        }
        $text = isset(Process::$exitCodes[$this->getExitCode()]) ? Process::$exitCodes[$this->getExitCode()] : '';
        return sprintf('Exit code `%s`: %s', $this->getExitCode(), $text);
    }
}
