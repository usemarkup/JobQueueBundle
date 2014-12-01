<?php

namespace Markup\JobQueueBundle\Service;

use BCC\ResqueBundle\Job;
use Doctrine\Common\Collections\ArrayCollection;
use Markup\JobQueueBundle\Exception\UnknownQueueException;
use Markup\JobQueueBundle\Exception\UnknownServerException;
use Markup\JobQueueBundle\Job\ConsoleCommandJob;
use Markup\JobQueueBundle\Model\QueueConfiguration;
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
    public function addJob(Job $job, $dateTime = null)
    {
        if ($dateTime === null) {
            $this->resque->enqueue($job);

            return;
        }
        $this->resque->enqueueAt($dateTime, $job);
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
        $queue = $this->getValidQueueName($queue);
        $job = new ConsoleCommandJob();
        $job->setCommand($command);
        $job->setQueue($queue);
        $job->setTimeout($timeout);
        $job->setIdleTimeout($idleTimeout);
        $this->addJob($job);
    }

    /**
     * Adds a named command to the job queue at a specific datetime
     * @param string  $command     A valid command for this application
     * @param string  $dateTime    The DateTime to execute the command
     * @param string  $queue       The name of a valid queue
     * @param integer $timeout     The amount of time to allow the command to run
     * @param integer $idleTimeout The amount of idle time to allow the command to run
     */
    public function addScheduledCommandJob(
        $command,
        \DateTime $dateTime,
        $queue = 'default',
        $timeout = 60,
        $idleTimeout = 60
    ) {
        $queue = $this->getValidQueueName($queue);
        $job = new ConsoleCommandJob();
        $job->setCommand($command);
        $job->setQueue($queue);
        $job->setTimeout($timeout);
        $job->setIdleTimeout($idleTimeout);
        $this->addJob($job, $dateTime);
    }

    public function getValidQueueName($queue)
    {

        $valid = [];

        foreach ($this->getQueueConfigurations() as $configuration) {
                $valid[] = sprintf('%s-%s', $configuration->getName(), $configuration->getServer());
        }

        if (in_array($queue, $valid)) {
            // is already valid... just return it
            return $queue;
        }

        // try to fetch a valid queue name from the defined queues for each server
        // will just search until it finds one that matches
        foreach ($this->getQueueConfigurations() as $configuration) {
            if ($queue === $configuration->getName()) {
                return sprintf('%s-%s', $configuration->getName(), $configuration->getServer());
            }
        }

        throw new UnknownQueueException(sprintf('Attempted access queue `%s` which is not defined by the application, valid queues are %s', $queue, implode(',', $valid)));
    }

    public function getQueueConfigurations($server = null)
    {
        $queueConfigurations = new ArrayCollection();

        foreach ($this->queues as $s => $queues) {
            foreach ($queues as $config) {
                $q = new QueueConfiguration($s, $config);
                $queueConfigurations->add($q);
            }
        }

        if (!$server) {
            return $queueConfigurations;
        }

        $filtered = $queueConfigurations->filter(function ($configuration) use ($server) {
            return $configuration->getServer() === $server;
        });

        if (count($filtered) === 0) {
            throw new UnknownServerException(sprintf('Queues for server %s do not exist', $server));
        }

        return $filtered;
    }

    public function setQueues($queues)
    {
        $this->queues = $queues;
    }

    public function getCountOfJobsInQueue($queue)
    {
        $queue = $this->getValidQueueName($queue);

        return $this->resque->getQueue($queue)->getSize();
    }
}
