<?php

namespace Markup\JobQueueBundle\Model;

use Markup\JobQueueBundle\Entity\ScheduledJob;

interface ScheduledJobRepositoryInterface
{
    /**
     * @return iterable<ScheduledJob>
     */
    public function fetchUnqueuedJobs();

    /**
     * @param ScheduledJob $scheduledJob
     */
    public function save(ScheduledJob $scheduledJob, $flush = false);

    public function isJobScheduledWithinRange(
        string $job,
        \DateTime $rangeFrom,
        \DateTime $rangeTo,
        ?array $arguments
    ): bool;

    public function hasUnQueuedDuplicate(string $job, ?array $arguments): bool;
}
