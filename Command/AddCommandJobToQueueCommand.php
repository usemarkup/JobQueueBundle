<?php

namespace Markup\JobQueueBundle\Command;

use Markup\JobQueueBundle\Service\JobManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command adds another command to the job queue
 */
class AddCommandJobToQueueCommand extends Command
{
    /**
     * @var JobManager
     */
    private $jobby;

    public function __construct(JobManager $jobby)
    {
        parent::__construct();
        $this->jobby = $jobby;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('markup:job_queue:add:command')
            ->setDescription('Adds a single job that executes a command via the job queue')
            ->addArgument(
                'cmd',
                InputArgument::REQUIRED,
                'The command to add'
            )
            ->addArgument(
                'topic',
                InputArgument::REQUIRED,
                'The topic to add the command to'
            )
            ->addOption(
                'timeout',
                't',
                InputOption::VALUE_OPTIONAL,
                'The timeout time for the command. Defaults to 60 seconds',
                60
            )
            ->addOption(
                'idle_timeout',
                'i',
                InputOption::VALUE_OPTIONAL,
                'The idle timeout time for the command. Defaults to 60 seconds',
                60
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = strval($input->getArgument('cmd'));
        $topic = strval($input->getArgument('topic'));
        $timeout = intval($input->getOption('timeout'));
        $idleTimeout = intval($input->getOption('idle_timeout'));

        $this->jobby->addCommandJob($command, $topic, $timeout, $idleTimeout);

        $output->writeln('<info>Added command to job queue</info>');
    }
}
