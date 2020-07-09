<?php

namespace Markup\JobQueueBundle\Entity;


class ScheduledJob
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $job;

    /**
     * @var string
     */
    private $topic;

    /**
     * @var \DateTime
     */
    private $scheduledTime;

    /**
     * @var bool
     */
    private $queued;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @param string $job
     * @param array $arguments
     * @param mixed $scheduledTime
     * @param mixed $topic
     */
    function __construct(string $job, array $arguments, $scheduledTime, $topic)
    {
        $this->job = $job;
        $this->arguments = $arguments;
        $this->scheduledTime = $scheduledTime;
        $this->topic = $topic;
        $this->queued = false;
    }


    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @return \DateTime
     */
    public function getScheduledTime()
    {
        return $this->scheduledTime;
    }

    /**
     * @param boolean $queued
     */
    public function setQueued($queued)
    {
        $this->queued = $queued;
    }

    /**
     * @return boolean
     */
    public function getQueued()
    {
        return $this->queued;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
