<?php

namespace Markup\JobQueueBundle\Model;

use Markup\JobQueueBundle\Entity\ScheduledJob;

interface ScheduledJobRepositoryInterface
{

    /**
     * @return mixed
     */
    public function fetchUnqueuedJobs();

    /**
     * @param ScheduledJob $scheduledJob
     */
    public function save(ScheduledJob $scheduledJob, $flush = false);
}
