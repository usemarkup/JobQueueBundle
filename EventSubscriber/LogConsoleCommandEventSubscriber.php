<?php

namespace Markup\JobQueueBundle\EventSubscriber;

use Markup\JobQueueBundle\Exception\UnknownJobLogException;
use Markup\JobQueueBundle\Model\JobLog;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Uses the `uuid` option to log a console command
 *
 * Will create a new JobLog if one does not exist, and mark it as running
 */
class LogConsoleCommandEventSubscriber implements EventSubscriberInterface
{
    use CheckUsingSymfony28Trait;

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
                ['onConsoleCommand', 10]
            ]
        ];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        // following jiggerypokery can be removed in symfony 2.8+
        if ($this->isUsingAtLeastSymfony28()) {
            $input = $event->getInput();
        } else {
            $event->getCommand()->mergeApplicationDefinition();
            $input = new ArgvInput();
            $input->bind($event->getCommand()->getDefinition());
        }

        $uuid = $input->getOption('uuid');
        if (!$uuid) {
            return;
        }

        // lookup job log repository for log and create one if it doesn't exist
        try { 
            $log = $this->jobLogRepository->getJobLog($uuid);
        } catch (UnknownJobLogException $e) {
            $commandString = $input->__toString();
            $log = $this->jobLogRepository->createAndSaveJobLog($commandString, $uuid);
        }
        
        // update job log to change status to running
        $log->setStatus(JobLog::STATUS_RUNNING);
        $log->setStarted((new \DateTime('now'))->format('U'));
        $this->jobLogRepository->save($log);
    }
}
