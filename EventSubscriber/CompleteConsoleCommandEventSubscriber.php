<?php

namespace Markup\JobQueueBundle\EventSubscriber;

use Markup\JobQueueBundle\Exception\MissingJobLogException;
use Markup\JobQueueBundle\Exception\UnknownJobLogException;
use Markup\JobQueueBundle\Model\JobLog;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
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

    /**
     * @param ConsoleTerminateEvent $event
     * @throws MissingJobLogException
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $input = $event->getInput();

        if (!$input->hasOption('uuid')) {
            return;
        }

        $uuid = $input->getOption('uuid');

        if (!$uuid) {
            return;
        }

        try {
            // lookup job log and save status
            $log = $this->jobLogRepository->getJobLog($uuid);

            if($log->getStatus() === JobLog::STATUS_RUNNING) {
                $log->setStatus(JobLog::STATUS_COMPLETE);
            }
            $log->setPeakMemoryUse($this->getPeakMemoryUse());
            $log->setCompleted((new \DateTime('now'))->format('U'));
            $this->jobLogRepository->save($log);
        } catch (UnknownJobLogException $e){
            // forgive exceptions
        }

    }

    /**
     * @param ConsoleExceptionEvent $event
     * @throws MissingJobLogException
     */
    public function onConsoleError(ConsoleEvent $event)
    {
        $input = $event->getInput();

        if (!$input->hasOption('uuid')) {
            return;
        }

        $uuid = $input->getOption('uuid');
        if (!$uuid) {
            return;
        }

        try {
            // lookup job log repository for log and create one if it doesn't exist
            $log = $this->jobLogRepository->getJobLog($uuid);
            if (!$log) {
                throw new MissingJobLogException(sprintf('No log exists for uuid: `%s`', $uuid));
            }

            $log->setStatus(JobLog::STATUS_FAILED);
            $this->jobLogRepository->save($log);
        } catch (UnknownJobLogException $e){
            // forgive exceptions
        }
    }

    private function getPeakMemoryUse()
    {
        return memory_get_peak_usage(true);
    }
}
