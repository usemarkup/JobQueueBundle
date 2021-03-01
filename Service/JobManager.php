<?php

namespace Markup\JobQueueBundle\Service;

use Markup\JobQueueBundle\Entity\Repository\ScheduledJobRepository;
use Markup\JobQueueBundle\Entity\ScheduledJob;
use Markup\JobQueueBundle\Job\ConsoleCommandJob;
use Markup\JobQueueBundle\Model\Job;
use Markup\JobQueueBundle\Publisher\JobPublisher;

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
     * @var ScheduledJobRepository
     */
    private $scheduledJobRepository;
    
    public function __construct(
        JobPublisher $publisher,
        ScheduledJobRepository $scheduledJobRepository
    ) {
        $this->publisher = $publisher;
        $this->scheduledJobRepository = $scheduledJobRepository;
    }

    public function addJob(Job $job, $supressLogging = false)
    {
        $this->publisher->publish($job, $supressLogging);
    }

    /**
     * Adds a named command to the job queue
     *
     * @param string $command A valid command for this application.
     * @param array $arguments
     * @param string $topic The name of a valid topic.
     * @param integer $timeout The amount of time to allow the command to run.
     * @param integer $idleTimeout The amount of idle time to allow the command to run. Defaults to the same as timeout.
     * @param bool $supressLogging Stops the job from being logged by the database
     */
    public function addConsoleCommandJob(string $command, array $arguments = [], $topic = 'default', $timeout = 60, $idleTimeout = null, $supressLogging = false)
    {
        if (stripos($command, " ") !== false) {
            throw new \InvalidArgumentException('Console command is not expected to have spaces within the name');
        }

        $args = [];
        $args['command'] = $command;
        $args['arguments'] = $arguments;
        $args['timeout'] = $timeout;
        $args['idleTimeout'] = $idleTimeout ?? $timeout;
        $job = new ConsoleCommandJob($args, $topic);

        $this->addJob($job, $supressLogging);
    }

    /**
     * Adds a named command to the job queue at a specific datetime
     *
     * @param string $command A valid command for this application.
     * @param \DateTime $dateTime The DateTime to execute the command.
     * @param array $arguments
     * @param string $topic The name of a valid topic.
     * @param int $timeout The amount of time to allow the command to run.
     * @param int $idleTimeout The amount of idle time to allow the command to run. Default to the same as timeout.
     */
    public function addScheduledConsoleCommandJob(
        $command,
        \DateTime $dateTime,
        array $arguments = [],
        $topic = 'default',
        $timeout = 60,
        $idleTimeout = null
    ) {
        if (stripos($command, " ") !== false) {
            throw new \InvalidArgumentException('Console command is not expected to have spaces within the name');
        }

        $args = [];
        $args['command'] = $command;
        $args['arguments'] = $arguments;
        $args['timeout'] = $timeout;
        $args['idleTimeout'] = $idleTimeout ?? $timeout;
        $job = new ConsoleCommandJob($args, $topic);

        $this->addScheduledJob($job, $dateTime);
    }
    
    public function addScheduledJob(ConsoleCommandJob $job, $scheduledTime): ScheduledJob
    {
        $scheduledJob = new ScheduledJob($job->getCommand(), $job->getArguments(), $scheduledTime, $job->getTopic());
        $this->scheduledJobRepository->save($scheduledJob, true);

        return $scheduledJob;
    }
}
