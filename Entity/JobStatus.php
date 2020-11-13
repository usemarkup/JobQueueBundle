<?php
declare(strict_types=1);

namespace Markup\JobQueueBundle\Entity;

class JobStatus
{
    /** @var ?int */
    private $id;

    /** @var string */
    private $command;

    /** @var string */
    private $arguments;

    /** @var bool */
    private $enabled;

    public function __construct(
        ?int $id,
        string $command,
        string $arguments,
        bool $enabled
    ) {
        $this->id = $id;
        $this->command = $command;
        $this->arguments = $arguments;
        $this->enabled = $enabled;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getArguments(): string
    {
        return $this->arguments;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    public function setArguments(string $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
