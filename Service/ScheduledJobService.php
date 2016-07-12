<?php

namespace Markup\JobQueueBundle\Service;

use Doctrine\ORM\EntityRepository;
use Markup\JobQueueBundle\Entity\ScheduledJob;
use Markup\JobQueueBundle\Model\Job;
use Markup\JobQueueBundle\Model\ScheduledJobRepositoryInterface;

class ScheduledJobService
{
    /**
     * @var ScheduledJobRepositoryInterface
     */
    private $scheduledJobRepository;

    /**
     * @param ScheduledJobRepositoryInterface $scheduledJobRepository
     */
    public function __construct(
        ScheduledJobRepositoryInterface $scheduledJobRepository
    ) {
        $this->scheduledJobRepository = $scheduledJobRepository;
    }

    /**
     * @param Job $job
     * @param \DateTime $scheduledTime
     * @return ScheduledJob
     */
    public function addScheduledJob(Job $job, $scheduledTime) {
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
        $this->scheduledJobRepository->save($scheduledJob, $flush);
        return $scheduledJob;
    }

    /**
     * @return mixed
     */
    public function getUnqueuedJobs() {
        return $this->scheduledJobRepository->fetchUnqueuedJobs();
    }
}
