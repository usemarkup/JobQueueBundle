<?php

namespace Markup\Bundle\JobQueueBundle\Model;

use Cron;

/**
 * A configuration object that indicates a console command, and the cron systax for when it should be run
 */
class RecurringConsoleComandConfiguration
{
    private $command;
    private $schedule;

    public function __construct($command, $queue, $schedule, $timeout = 60)
    {
        $this->command = $command;
        $this->schedule = $schedule;
        $this->queue = $queue;
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
