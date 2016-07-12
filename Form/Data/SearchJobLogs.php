<?php

namespace Markup\JobQueueBundle\Form\Data;

use Pagerfanta\Adapter\FixedAdapter;

class SearchJobLogs
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $commandConfigurationId;

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

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCommandConfigurationId()
    {
        return $this->commandConfigurationId;
    }

    /**
     * @param string $commandConfigurationId
     */
    public function setCommandConfigurationId($commandConfigurationId)
    {
        $this->commandConfigurationId = $commandConfigurationId;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * @param string $before
     */
    public function setBefore($before)
    {
        $this->before = $before;
    }
    /**
     * @return string
     */
    public function getSince()
    {
        return $this->since;
    }

    /**
     * @param string $since
     */
    public function setSince($since)
    {
        $this->since = $since;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage($page)
    {
        if (!$page) {
            return;
        }
        $this->page = $page;
    }

    /**
     * @return int Gets a zero indexed version of the page for use in a query
     */
    public function getPageOffset()
    {
        return max(0, $this->getPage() - 1);
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

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'since' => $this->getSince(),
            'before' => $this->getBefore(),
            'status' => $this->getStatus(),
            'command_configuration_id' => $this->getCommandConfigurationId()
        ];
    }

}
