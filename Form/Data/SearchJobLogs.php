<?php

namespace Markup\JobQueueBundle\Form\Data;

class SearchJobLogs
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $command;

    /**
     * @var \DateTime
     */
    private $before;

    /**
     * @var string
     */
    private $status;

    /**
     * @var \DateTime
     */
    private $since;

    /**
     * @var int
     */
    private $page;

    public function __construct()
    {
        $this->page = 1;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function setCommand(string $command)
    {
        $this->command = $command;
    }

    public function getBefore(): ?\DateTime
    {
        return $this->before;
    }

    public function setBefore(?\DateTime $before = null)
    {
        $this->before = $before;
    }

    public function getSince(): ?\DateTime
    {
        return $this->since;
    }

    public function setSince(?\DateTime $since = null)
    {
        $this->since = $since;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * @return bool Is search for a single id?
     */
    public function isIdSearch()
    {
        return $this->getId() !== null;
    }

    /**
     * Is this a search for a commandId or Status?
     *
     * @return bool
     */
    public function isDiscriminatorSearch()
    {
        if ($this->getStatus() || $this->getCommand()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool Is this a search for datetime range only
     */
    public function isRangeSearchOnly()
    {
        if (!$this->getBefore() && $this->getSince()) {
            return false;
        }
        if (!$this->getStatus() && !$this->getCommand() && !$this->getId()) {
            return true;
        }
        return false;
    }

    public function setPage(int $page)
    {
        $this->page = $page;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'since' => $this->getSince(),
            'before' => $this->getBefore(),
            'status' => $this->getStatus(),
            'command' => $this->getCommand()
        ];
    }

}
