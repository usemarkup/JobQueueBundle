<?php

namespace Markup\Bundle\JobQueueBundle\Command;

use Markup\Bundle\JobQueueBundle\Job\Test\BadJob;
use Markup\Bundle\JobQueueBundle\Job\Test\ErrorJob;
use Markup\Bundle\JobQueueBundle\Job\Test\ExceptionJob;
use Markup\Bundle\JobQueueBundle\Job\Test\SleepJob;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command can add a variety of test jobs
 * Exists for testing and development purposes of the job queue
 */
class AddTestJobCommand extends ContainerAwareCommand
{

    const TYPE_SLEEP = 'sleep';
    const TYPE_BAD = 'bad';
    const TYPE_ERROR = 'error';
    const TYPE_EXCEPTION = 'exception';

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('markup:job_queue:add:test')
            ->setDescription('Adds a single job that sleeps for a period of time (required) to allow testing of the job queue')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'The type of job to add. Should be one of `sleep`, `bad`, or `exception`'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resque = $this->getContainer()->get('bcc_resque.resque');
        $type = $input->getArgument('type');

        switch ($type) {
            case self::TYPE_SLEEP:
                $job = new SleepJob(30);
                break;
            case self::TYPE_BAD:
                $job = new BadJob();
                break;
            case self::TYPE_ERROR:
                $job = new ErrorJob();
                break;
            case self::TYPE_EXCEPTION:
                $job = new ExceptionJob();
                break;
            default:
                throw new \Exception(sprintf('Unknown job of type %s specified', $type));
                break;
        }

        // enqueue your job
        $resque->enqueue($job);

        $output->writeln(sprintf('<info>Added %s job</info>', $type));
    }
}
