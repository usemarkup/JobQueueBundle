<?php

namespace Markup\JobQueueBundle\EventSubscriber;

use Markup\JobQueueBundle\Entity\JobLog;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Uses the `uuid` option to log a console command
 *
 * Will create a new JobLog if one does not exist, and mark it as running
 */
class LogConsoleCommandEventSubscriber implements EventSubscriberInterface
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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => [
                ['onConsoleCommand', -9999999]
            ]
        ];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $input = $event->getInput();

        if (!$input->hasOption('uuid')) {
            return;
        }

        $uuid = $input->getOption('uuid');
        if (!$uuid) {
            return;
        }

        // lookup job log repository for log and create one if it doesn't exist

        $log = $this->jobLogRepository->findJobLog($uuid);
        if (!$log) {
            $commandString = $input->__toString();
            $log = $this->jobLogRepository->createAndSaveJobLog($commandString, $uuid);
        }
        
        // update job log to change status to running
        $log->setStatus(JobLog::STATUS_RUNNING);
        $log->setStarted(new \DateTime());
        $this->jobLogRepository->save($log);
    }
}
