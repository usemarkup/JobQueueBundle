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
     * @var string|null
     */
    private $exitCode;

    public function __construct(
        string $command,
        string $uuid = null,
        string $topic = self::TOPIC_UNDEFINED,
        ?\DateTime $added = null,
        string $status = self::STATUS_ADDED,
        ?string $output = null,
        ?string $exitCode = null,
        ?int $peakMemoryUse = null,
        ?\DateTime $completed = null,
        ?\DateTime $started = null
    ) {
        $this->command = $command;
        $this->topic = $topic;
        $this->added = $added ?? new \DateTime();
        $this->completed = $completed;
        $this->started = $started;
        $this->status = $status;
        $this->output = $output;
        $this->peakMemoryUse = $peakMemoryUse;
        $this->exitCode = $exitCode;

        // none of these values are mutable so are suitable to generate uuid
        // the 'added' makes it unique
        if (!$uuid) {
            $uuid = $this->generateUuid($command, $topic, $this->added->format('U'));
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
    ): self {
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

    public function toCompressedArray(): array
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
        if (!$this->getCompleted() || !$this->getStarted()) {
            return 0;
        }
        $diff = $this->getCompleted()->diff($this->getStarted());

        // convert diff to duration seconds
        $duration = ($diff->days * 3600 * 24) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;
        if ($duration === 0) {
            $duration = 1;
        }

        return intval($duration);
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

    public function getExitCode(): ?string
    {
        return $this->exitCode;
    }

    private function generateUuid(string $command, string $topic, string $added): string
    {
        return hash('SHA256', $command.$topic.$added);
    }

    public function setCompleted(?\DateTime $completed = null)
    {
        $this->completed = $completed;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    public function setOutput(?string $output = null)
    {
        $this->output = $output;
    }

    public function setPeakMemoryUse(?int $peakMemoryUse = null)
    {
        $this->peakMemoryUse = $peakMemoryUse;
    }

    public function setExitCode(?string $exitCode)
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
        if (!$this->getExitCode()) {
            return '';
        }
        $text = isset(Process::$exitCodes[$this->getExitCode()]) ? Process::$exitCodes[$this->getExitCode()] : '';
        return sprintf('Exit code `%s`: %s', $this->getExitCode(), $text);
    }
}
