<?php

namespace Markup\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command reads the recurring job configuration
 * and adds any recurring commands to the specified job queue
 * also performs maintenance on the recurring job log
 *
 * This command should be run every minute via crontab
 */
class AddRecurringConsoleJobToQueueCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('markup:job_queue:recurring:add')
            ->setDescription('Adds any configured recurring jobs, which are due NOW, to the specified job queue');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->addRecurringJobs($output);
        $this->maintainJobLogs();
    }

    /**
     * @param OutputInterface $output
     */
    private function addRecurringJobs(OutputInterface $output)
    {
        $recurringConsoleCommandReader = $this->getContainer()->get('markup_job_queue.reader.recurring_console_command');

        $due = $recurringConsoleCommandReader->getDue();

        foreach ($due as $configuration) {

            if ($configuration->getEnvs()) {
                $env = $this->getContainer()->get('kernel')->getEnvironment();

                if (!in_array($env, $configuration->getEnvs())) {
                    $output->writeln(
                        sprintf(
                            '<info>Skipping `%s`, not to run in this env</info>',
                            $configuration->getCommand()
                        )
                    );
                    continue;
                }
            }
            $this->getContainer()->get('jobby')->addCommandJob(
                $configuration->getCommand(),
                $configuration->getTopic(),
                $configuration->getTimeout(),
                $configuration->getTimeout()
            );
            $message = sprintf(
                '%s Added command `%s` with the topic `%s`',
                $configuration->previousRun()->format('c'),
                $configuration->getCommand(),
                $configuration->getTopic()
            );
            if ($configuration->nextRun()) {
                $message = sprintf('%s. Will next be added %s', $message, $configuration->nextRun()->format('r'));
            }
            $output->writeln(sprintf('<info>%s</info>', $message));
        }

        $this->getContainer()->get('markup_job_queue.repository.cron_health')->set();
    }

    private function maintainJobLogs()
    {
        $this->getContainer()->get('markup_job_queue.repository.job_log')->removeExpiredJobsFromSecondaryIndexes();
    }
}
