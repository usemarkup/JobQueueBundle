<?php

namespace Markup\JobQueueBundle\Entity;

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
     * @var string|null
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
     * @var int|null
     */
    private $exitCode;

    public function __construct(
        string $command,
        string $uuid,
        string $topic = self::TOPIC_UNDEFINED
    ) {
        $this->command = $command;
        $this->uuid = $uuid;
        $this->topic = $topic;
        $this->added = $added ?? new \DateTime();
        $this->status = self::STATUS_ADDED;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getAdded(): ?\DateTime
    {
        return $this->added;
    }

    public function getCompleted(): ?\DateTime
    {
        return $this->completed;
    }

    /**
     * Returns duration in seconds
     * @return int
     */
    public function getDuration(): int
    {
        $completed = $this->getCompleted();
        $started = $this->getStarted();

        if (!$completed || !$started) {
            return 0;
        }

        return $completed->getTimestamp() - $started->getTimestamp();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function getPeakMemoryUse(): ?int
    {
        return $this->peakMemoryUse;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getExitCode(): int
    {
        return $this->exitCode ?? 0;
    }

    public function setCompleted(?\DateTime $completed = null)
    {
        $this->completed = $completed;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    public function setOutput(string $output)
    {
        $this->output = $output;
    }

    public function setPeakMemoryUse(?int $peakMemoryUse = null)
    {
        $this->peakMemoryUse = $peakMemoryUse;
    }

    public function setExitCode(int $exitCode)
    {
        $this->exitCode = $exitCode;
    }

    public function getStarted(): ?\DateTime
    {
        return $this->started;
    }

    public function setStarted(?\DateTime $started = null): void
    {
        $this->started = $started;
    }

    public function getExitCodeText(): string
    {
        if (!$this->exitCode) {
            return '';
        }

        $text = isset(Process::$exitCodes[$this->getExitCode()]) ? Process::$exitCodes[$this->getExitCode()] : '';

        return sprintf('Exit code `%s`: %s', $this->getExitCode(), $text);
    }
}
