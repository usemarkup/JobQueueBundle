<?php

namespace Markup\JobQueueBundle\Service;

use BCC\ResqueBundle\Job;
use Markup\JobQueueBundle\Exception\UnknownQueueException;
use Markup\JobQueueBundle\Job\ConsoleCommandJob;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * Controller for adding jobs to a queue
 */
class JobManager
{
    private $resque;
    private $queues;

    public function __construct($resque)
    {
        $this->resque = $resque;
        $this->queues = [];
    }

    /**
     * Adds a job to the resque queue
     * @param Job $job
     */
    public function addJob(Job $job)
    {
        $this->resque->enqueue($job);
    }

    /**
     * Adds a named command to the job queue
     * @param string  $command     A valid command for this application
     * @param string  $queue       The name of a valid queue
     * @param integer $timeout     The amount of time to allow the command to run
     * @param integer $idleTimeout The amount of idle time to allow the command to run
     */
    public function addCommandJob($command, $queue = 'default', $timeout = 60, $idleTimeout = 60)
    {
        if ($this->isValidQueue($queue) === false) {
            throw new UnknownQueueException(sprintf('Attempted to add to queue `%s` which is not defined by the application, valid queues are %s', $queue, implode(',', $this->queues)));
        }
        $job = new ConsoleCommandJob();
        $job->setCommand($command);
        $job->setQueue($queue);
        $job->setTimeout($timeout);
        $job->setIdleTimeout($idleTimeout);
        $this->addJob($job);
    }

    public function isValidQueue($queue)
    {
        return in_array($queue, $this->queues);
    }

    public function getQueues()
    {
        return $this->queues;
    }

    public function setQueues($queues)
    {
        $this->queues = $queues;
    }
}
