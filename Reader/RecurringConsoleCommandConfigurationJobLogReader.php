<?php

namespace Markup\JobQueueBundle\Reader;

use Doctrine\Common\Collections\ArrayCollection;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use Markup\JobQueueBundle\Service\RecurringConsoleCommandReader;
use Markup\JobQueueBundle\Form\Data\SearchJobLogs;

/**
 * Reads information about logged jobs for all RecurringConsoleCommandConfiguration
 */
class RecurringConsoleCommandConfigurationJobLogReader
{

    /**
     * @var RecurringConsoleCommandReader
     */
    private $recurringConsoleCommandReader;

    /**
     * @var JobLogRepository
     */
    private $jobLogRepository;

    /**
     * @param RecurringConsoleCommandReader $recurringConsoleCommandReader
     * @param JobLogRepository              $jobLogRepository
     */
    public function __construct(
        RecurringConsoleCommandReader $recurringConsoleCommandReader,
        JobLogRepository $jobLogRepository
    ) {

        $this->recurringConsoleCommandReader = $recurringConsoleCommandReader;
        $this->jobLogRepository = $jobLogRepository;
    }

    /**
     * Get an array of JobLogCollections keyed by configuration uuid
     *
     * @param int $maxQuantity
     * @return array
     */
    public function getJobLogCollections($maxQuantity = 5)
    {
        $collection = [];
        $configurations = $this->recurringConsoleCommandReader->getConfigurations();
        foreach ($configurations as $configuration) {
            $searchData = new SearchJobLogs();
            $searchData->setCommand($configuration->getCommand());
            $jobLogs = $this->jobLogRepository->getJobLogCollection($searchData, $maxQuantity);

            $collection[$configuration->getUuid()] = $jobLogs;
        }

        return $collection;
    }

}
