<?php

namespace Markup\JobQueueBundle\Command;

use Markup\JobQueueBundle\Job\BadJob;
use Markup\JobQueueBundle\Job\ExceptionJob;
use Markup\JobQueueBundle\Job\MonologErrorJob;
use Markup\JobQueueBundle\Job\SleepJob;
use Markup\JobQueueBundle\Job\WorkJob;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command can immediately runs a variety of test jobs
 * Exists for testing and development purposes of the job queue
 */
class RunTestJobCommand extends ContainerAwareCommand
{
    const TYPE_SLEEP = 'sleep';
    const TYPE_BAD = 'bad';
    const TYPE_ERROR = 'error';
    const TYPE_EXCEPTION = 'exception';
    const TYPE_WORK = 'work';

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('markup:job_queue:run:test')
            ->setDescription('Adds a single job to allow testing of the job queue')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'The type of job to add. Should be one of `sleep`, `bad` (fatal error), `error` (monolog error), `work` (cryptography) or `exception` (uncaught exception)'
            )
            ->addArgument(
                'topic',
                InputArgument::OPTIONAL,
                'The topic of the test job (defaults to `test`)',
                'test'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');
        $topic = $input->getArgument('topic');

        switch ($type) {
            case self::TYPE_SLEEP:
                $job = new SleepJob(['time' => 3], $topic);
                break;
            case self::TYPE_BAD:
                $job = new BadJob([], $topic);
                break;
            case self::TYPE_ERROR:
                $job = new MonologErrorJob([], $topic);
                break;
            case self::TYPE_EXCEPTION:
                $job = new ExceptionJob([], $topic);
                break;
            case self::TYPE_WORK:
                $job = new WorkJob(['units' => 200, 'complexity' => 32], $topic);
                break;
            default:
                throw new \Exception(sprintf('Unknown job of type %s specified', $type));
                break;
        }

        $job->validate();
        $job->run($this->getContainer());
    }
}
