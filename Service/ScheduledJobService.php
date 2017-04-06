<?php

namespace Markup\JobQueueBundle\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Markup\JobQueueBundle\Entity\Repository\ScheduledJobRepository;
use Markup\JobQueueBundle\Entity\ScheduledJob;
use Markup\JobQueueBundle\Model\Job;

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
     * @param Job $job
     * @param \DateTime $scheduledTime
     * @return ScheduledJob
     */
    public function addScheduledJob(Job $job, $scheduledTime)
    {
        $scheduledJob = new ScheduledJob($job->getCommand(), $scheduledTime, $job->getTopic());
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
     * @return mixed
     */
    public function getUnqueuedJobs()
    {
        return $this->getScheduledJobRepository()->fetchUnqueuedJobs();
    }

    /**
     * @return ObjectRepository|ScheduledJobRepository
     */
    private function getScheduledJobRepository()
    {
        return $this->managerRegistry->getRepository(ScheduledJob::class);
    }
}
