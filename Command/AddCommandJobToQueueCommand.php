<?php

namespace Markup\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command adds another command to the job queue
 */
class AddCommandJobToQueueCommand extends ContainerAwareCommand
{

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
                'queue',
                InputArgument::REQUIRED,
                'The queue to add the command to'
            )
            ->addArgument(
                'server',
                InputArgument::REQUIRED,
                'The server for which the job is being added'
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
        $command = $input->getArgument('cmd');
        $queue = $input->getArgument('queue');
        $server = $input->getArgument('server');
        $timeout = $input->getOption('timeout');
        $idleTimeout = $input->getOption('idle_timeout');

        $queueServer = sprintf('%s-%s', $queue, $server);

        $jobby = $this->getContainer()->get('jobby')->addCommandJob($command, $queueServer, $timeout, $idleTimeout);

        $output->writeln('<info>Added command to job queue</info>');
    }
}
