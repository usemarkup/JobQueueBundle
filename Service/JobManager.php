<?php

namespace Markup\JobQueueBundle\Service;

use Markup\JobQueueBundle\Job\ConsoleCommandJob;
use Markup\JobQueueBundle\Model\Job;
use Markup\JobQueueBundle\Publisher\JobPublisher;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * Controller for adding jobs to a queue
 * or scheduling them to be added at a later date
 */
class JobManager
{
    private $resque;

    public function __construct(JobPublisher $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Adds a job to the resque queue
     * @param Job $job
     */
    public function addJob(Job $job, $dateTime = null)
    {
        if ($dateTime === null) {
            $this->publisher->publish($job);

            return;
        }
        throw new \Exception('Scheduled jobs are unimplemented');
    }

    /**
     * Adds a named command to the job queue
     * @param string  $command     A valid command for this application
     * @param string  $topic       The name of a valid topic
     * @param integer $timeout     The amount of time to allow the command to run
     * @param integer $idleTimeout The amount of idle time to allow the command to run
     */
    public function addCommandJob($command, $topic = 'default', $timeout = 60, $idleTimeout = 60)
    {
        $args = [];
        $args['command'] = $command;
        $args['timeout'] = $timeout;
        $args['idleTimeout'] = $idleTimeout;
        $job = new ConsoleCommandJob($args, $topic);
        $this->addJob($job);
    }

    /**
     * Adds a named command to the job queue at a specific datetime
     * @param string  $command     A valid command for this application
     * @param string  $dateTime    The DateTime to execute the command
     * @param string  $topic       The name of a valid topic
     * @param integer $timeout     The amount of time to allow the command to run
     * @param integer $idleTimeout The amount of idle time to allow the command to run
     */
    public function addScheduledCommandJob(
        $command,
        \DateTime $dateTime,
        $topic = 'default',
        $timeout = 60,
        $idleTimeout = 60
    ) {
        $args = [];
        $args['command'] = $command;
        $args['timeout'] = $timeout;
        $args['idleTimeout'] = $idleTimeout;
        $job = new ConsoleCommandJob($args, $topic);
        $this->addJob($job, $dateTime);
    }
}
