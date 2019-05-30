<?php

namespace Markup\JobQueueBundle\Form\Handler;

use Markup\JobQueueBundle\Repository\JobLogRepository;
use Pagerfanta\Pagerfanta;
use Markup\JobQueueBundle\Form\Data\SearchJobLogs as SearchJobLogsData;

/**
 * Handles searches by returning pagerfanatas containing search results
 */
class SearchJobLogs
{
    /**
     * @var JobLogRepository
     */
    private $jobLogRepository;

    /**
     * @param JobLogRepository $jobLogRepository
     */
    public function __construct(JobLogRepository $jobLogRepository)
    {
        $this->jobLogRepository = $jobLogRepository;
    }

    /**
     * @param SearchJobLogsData $options
     *
     * @param int $page
     *
     * @return Pagerfanta
     */
    public function handle(SearchJobLogsData $options, int $page = 1)
    {
        return $this->jobLogRepository->getJobLogs($options, 10, $page);
    }
}
