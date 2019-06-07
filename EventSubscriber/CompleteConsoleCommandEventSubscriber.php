<?php

namespace Markup\JobQueueBundle\EventSubscriber;

use Markup\JobQueueBundle\Entity\JobLog;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Uses the `uuid` option to log a console command
 *
 * If exception then will log as `failed`.
 *
 * If successful will save the status as `processed` and save the memory usage
 * Note that output cannot be captured directly here - the whole process must be wrapped and the output captured
 * and save to the log against the same uuid
 *
 */
class CompleteConsoleCommandEventSubscriber implements EventSubscriberInterface
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
            ConsoleEvents::TERMINATE => [
                ['onConsoleTerminate', 10]
            ],
            ConsoleEvents::ERROR => [
                ['onConsoleError', 10]
            ],
        ];
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $input = $event->getInput();

        if (!$input->hasOption('uuid')) {
            return;
        }

        $uuid = $input->getOption('uuid');

        if (!$uuid) {
            return;
        }

        $log = $this->jobLogRepository->findJobLog(strval($uuid));

        if (!$log) {
            return;
        }

        if ($log->getStatus() === JobLog::STATUS_RUNNING) {
            $log->setStatus(JobLog::STATUS_COMPLETE);
        }

        $log->setPeakMemoryUse(memory_get_peak_usage(true));
        $log->setCompleted(new \DateTime());

        $this->jobLogRepository->save($log);
    }

    public function onConsoleError(ConsoleEvent $event): void
    {
        $input = $event->getInput();

        if (!$input->hasOption('uuid')) {
            return;
        }

        $uuid = $input->getOption('uuid');

        if (!$uuid) {
            return;
        }

        $log = $this->jobLogRepository->findJobLog(strval($uuid));
        if (!$log) {
            return;
        }

        $log->setStatus(JobLog::STATUS_FAILED);
        $this->jobLogRepository->save($log);
    }
}
