<?php

namespace Markup\JobQueueBundle\Model;

use Cron;

/**
 * A configuration object that indicates a console command, and the cron systax for when it should be run
 */
class RecurringConsoleCommandConfiguration
{
    private $command;
    private $schedule;
    private $queue;
    private $server;
    private $timeout;

    public function __construct($command, $queue, $schedule, $server, $timeout = 60)
    {
        $this->command = $command;
        $this->schedule = $schedule;
        $this->queue = $queue;
        $this->server = $server;
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
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @return integer
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
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
