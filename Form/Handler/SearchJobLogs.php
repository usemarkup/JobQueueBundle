<?php

namespace Markup\JobQueueBundle\Form\Handler;

use Markup\JobQueueBundle\Repository\JobLogRepository;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Markup\JobQueueBundle\Form\Data\SearchJobLogs as SearchJobLogsData;

/**
 * Handles searches by returning pagerfanatas containing search results
 */
class SearchJobLogs
{
    const PAGINATION_LIMIT = 10;

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
     * @param SearchJobLogs $data
     * @returns PagerFanta
     */
    public function handle(SearchJobLogsData $options)
    {
        $count = $this->jobLogRepository->getJobLogs($options, self::PAGINATION_LIMIT, $countOnly = true);
        $results = $this->jobLogRepository->getJobLogs($options, self::PAGINATION_LIMIT);

        $adapter = new FixedAdapter($count, $results);
        $logs = new Pagerfanta($adapter);

        $logs->setCurrentPage($options->getPage());
        $logs->setMaxPerPage(self::PAGINATION_LIMIT);

        return $logs;
    }
}
