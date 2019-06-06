<?php

namespace Markup\JobQueueBundle\EventSubscriber;

use Markup\JobQueueBundle\Entity\JobLog;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ramsey\Uuid\Uuid;

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

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $input = $event->getInput();

        if (!$input->hasOption('uuid')) {
            return;
        }

        $uuid = $input->getOption('uuid');

        if (!$uuid) {
            return;
        }

        $uuid = strval($uuid);

        $log = $this->jobLogRepository->findJobLog($uuid);

        if (!$log) {
            if (!method_exists($input, '__toString')) {
                return;
            }

            $commandString = $input->__toString();
            $log = new JobLog($commandString, $uuid);

            $this->jobLogRepository->add($log);
        }
        
        $log->setStatus(JobLog::STATUS_RUNNING);
        $log->setStarted(new \DateTime());

        $this->jobLogRepository->save($log);
    }
}
