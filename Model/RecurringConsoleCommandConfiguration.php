<?php

namespace Markup\JobQueueBundle\Model;

use Cron;

/**
 * A configuration object that indicates a console command
 * and the cron syntax for when it should be run
 */
class RecurringConsoleCommandConfiguration
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var string
     */
    private $schedule;

    /**
     * @var string
     */
    private $topic;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var array|null
     */
    private $envs;

    /**
     * @param string  $command
     * @param string  $topic
     * @param string  $schedule
     * @param string|null  $description
     * @param integer|null $timeout
     * @param array|null $envs
     */
    public function __construct($command, $topic, $schedule, $description = null, $timeout = 60, $envs = null)
    {
        $this->command = $command;
        $this->schedule = $schedule;
        $this->topic = str_replace('-', '_', $topic);
        $this->timeout = $timeout;
        $this->description = $description;
        $this->envs = $envs;
    }

    /**
     * Returns a hash which can be used to uniquely identify this configuration
     *
     * @return string
     */
    public function getUuid()
    {
        return hash('SHA1', serialize($this));
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
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @return integer
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return boolean
     */
    public function isDue($time = 'now')
    {
        $cron = Cron\CronExpression::factory($this->getSchedule());

        return $cron->isDue($time);
    }

    /**
     * @return \DateTime
     */
    public function nextRun($time = 'now')
    {
        $cron = Cron\CronExpression::factory($this->getSchedule());

        return $cron->getNextRunDate($time);
    }

    /**
     * This method will return the 'current' minute if the job is 'due' currently
     *
     * @return \DateTime
     */
    public function previousRun($time = 'now')
    {
        $cron = Cron\CronExpression::factory($this->getSchedule());

        if ($cron->isDue($time)) {
            $now = new \DateTime();
            $now->setTime(intval($now->format('H')), intval($now->format('i')));
            return $now;
        }
        return $cron->getPreviousRunDate($time);
    }

    /**
     * The number of seconds between now (or time passed) and the next time the command will be run
     *
     * @param mixed $time
     * @return integer
     */
    public function secondsUntilNextRun($time = 'now')
    {
        $due = $this->nextRun($time);
        if (!$time instanceof \DateTime) {
            $time = new \DateTime($time);
        }
        $diff = $due->getTimestamp() - $time->getTimestamp();

        return $diff;
    }

    /**
     * The number of seconds between now (or time passed) and the next time the command will be run
     *
     * @param mixed $time
     * @return integer
     */
    public function secondsSincePreviousRun($time = 'now')
    {
        $previous = $this->previousRun($time);
        if (!$time instanceof \DateTime) {
            $time = new \DateTime($time);
        }

        return $time->getTimestamp() - $previous->getTimestamp();
    }

    /**
     * The number of seconds (interval) between last run and next run
     *
     * @param mixed $time
     * @return int
     */
    public function secondsBetweenPreviousAndNextRun($time = 'now')
    {
        $previous = $this->previousRun($time);
        $due = $this->nextRun($time);

        return $due->getTimestamp() - $previous->getTimestamp();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return array|null
     */
    public function getEnvs(): ?array
    {
        return $this->envs;
    }
}
