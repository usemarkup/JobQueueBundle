<?php

namespace Markup\JobQueueBundle\Model;

use Cron;

/**
 * A configuration object that indicates a console command
 * and the cron systax for when it should be run
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
     * @var string
     */
    private $timeout;

    /**
     * @param string  $command
     * @param string  $topic
     * @param string  $schedule
     * @param integer $timeout
     */
    public function __construct($command, $topic, $schedule, $timeout = 60)
    {
        $this->command = $command;
        $this->schedule = $schedule;
        $this->topic = $topic;
        $this->timeout = $timeout;
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
     * @return \DateTime
     */
    public function previousRun($time = 'now')
    {
        $cron = Cron\CronExpression::factory($this->getSchedule());

        return $cron->getPreviousRunDate($time);
    }
}
