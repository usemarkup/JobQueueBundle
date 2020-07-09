<?php

namespace Markup\JobQueueBundle\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use Markup\JobQueueBundle\Entity\ScheduledJob;
use Markup\JobQueueBundle\Job\ConsoleCommandJob;
use Markup\JobQueueBundle\Model\ScheduledJobRepositoryInterface;

class ScheduledJobService
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        ManagerRegistry $managerRegistry
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param ConsoleCommandJob $job
     * @param \DateTime|string $scheduledTime
     * @return ScheduledJob
     */
    public function addScheduledJob(ConsoleCommandJob $job, $scheduledTime)
    {
        $scheduledJob = new ScheduledJob($job->getCommand(), $job->getArguments(), $scheduledTime, $job->getTopic());
        $this->save($scheduledJob, true);

        return $scheduledJob;
    }

    /**
     * @param ScheduledJob $scheduledJob
     * @param bool $flush
     * @return ScheduledJob
     */
    public function save(ScheduledJob $scheduledJob, $flush = false)
    {
        $this->getScheduledJobRepository()->save($scheduledJob, $flush);

        return $scheduledJob;
    }

    /**
     * @return iterable<ScheduledJob>
     */
    public function getUnqueuedJobs()
    {
        return $this->getScheduledJobRepository()->fetchUnqueuedJobs();
    }

    private function getScheduledJobRepository(): ScheduledJobRepositoryInterface
    {
        /** @var ScheduledJobRepositoryInterface $repository */
        $repository = $this->managerRegistry->getRepository(ScheduledJob::class);

        return $repository;
    }
}
