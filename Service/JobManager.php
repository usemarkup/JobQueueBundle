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

    /**
     * @var JobPublisher
     */
    private $publisher;

    /**
     * @var ScheduledJobService
     */
    private $scheduledJob;

    public function __construct(
        JobPublisher $publisher,
        ScheduledJobService $scheduledJobService
    ) {
        $this->publisher = $publisher;
        $this->scheduledJob = $scheduledJobService;
    }

    /**
     * Adds a job to the resque queue
     * @param Job $job
     */
    public function addJob(Job $job, $dateTime = null)
    {
        if ($dateTime === null) {
            $this->publisher->publish($job);
        } else {
            $this->scheduledJob->addScheduledJob($job, $dateTime);
        }
    }

    /**
     * Adds a named command to the job queue
     * @param string  $command     A valid command for this application.
     * @param string  $topic       The name of a valid topic.
     * @param integer $timeout     The amount of time to allow the command to run.
     * @param integer $idleTimeout The amount of idle time to allow the command to run. Defaults to the same as timeout.
     */
    public function addCommandJob($command, $topic = 'default', $timeout = 60, $idleTimeout = null)
    {
        $args = [];
        $args['command'] = $command;
        $args['timeout'] = $timeout;
        $args['idleTimeout'] = $idleTimeout ?? $timeout;
        $job = new ConsoleCommandJob($args, $topic);
        $this->addJob($job);
    }

    /**
     * Adds a named command to the job queue at a specific datetime
     * @param string  $command     A valid command for this application.
     * @param \DateTimeInterface  $dateTime    The DateTime to execute the command.
     * @param string  $topic       The name of a valid topic.
     * @param integer $timeout     The amount of time to allow the command to run.
     * @param integer $idleTimeout The amount of idle time to allow the command to run. Default to the same as timeout.
     */
    public function addScheduledCommandJob(
        $command,
        \DateTimeInterface $dateTime,
        $topic = 'default',
        $timeout = 60,
        $idleTimeout = null
    ) {
        $args = [];
        $args['command'] = $command;
        $args['timeout'] = $timeout;
        $args['idleTimeout'] = $idleTimeout ?? $timeout;
        $job = new ConsoleCommandJob($args, $topic);
        $this->addJob($job, $dateTime);
    }
}
