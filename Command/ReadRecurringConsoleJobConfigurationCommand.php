<?php

namespace Markup\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command reads the recurring job configuration and displays a table showing information about scheduled jobs
 */
class ReadRecurringConsoleJobConfigurationCommand extends ContainerAwareCommand
{

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('markup:job_queue:recurring:view')
            ->setDescription('Views the current application configuration for recurring console jobs, showing the next run time')
            ->addOption(
                'time',
                't',
                InputOption::VALUE_OPTIONAL,
                'If set - the command takes this value as the current time when showing job information. Value needs to be a valid datetime constructor.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = new \DateTime('now');
        if ($input->hasOption('time')) {
            $selectedTime = $input->getOption('time');
            try {
                $throwaway = new \DateTime($selectedTime);
                $time = $throwaway;
            } catch (\Exception $e) {
                // dont do - this handles bad user input and will default to 'now'
            }
        }

        $recurringConsoleCommandReader = $this->getContainer()->get('markup_admin_job_queue_recurring_console_command_reader');

        $output->writeln(sprintf('<info>Treating current time as %s</info>', $time->format('r')));
        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(['command', 'topic', 'schedule', 'valid command?', 'due?', 'next run?']);
        foreach ($recurringConsoleCommandReader->getConfigurations() as $configuration) {
            $row = [];
            $row[] = $configuration->getCommand();
            $row[] = $configuration->getTopic();
            $row[] = $configuration->getSchedule();
            $row[] = $this->isCommandValid($configuration->getCommand()) ? '✓' : '✗';
            $row[] = $configuration->isDue() ? '✓' : '✗';
            if ($configuration->nextRun()) {
                $row[] = $configuration->nextRun()->format('r');
            } else {
                $row[] = '-';
            }
            $table->addRow(
                $row
            );
        }

        $table->render($output);
    }

    /**
     * Uses the process component to determine if a command is valid, by looking at the output of cmd:xyz --help
     * @param  $command
     * @return boolean
     */
    private function isCommandValid($command)
    {
        // because command contains the arguments, we need the cmd part only
        // @TODO: Split the CommandJob into cmd, option and argument parts
        $cmdParts = explode(' ', $command);
        try {
            $cmd = $this->getApplication()->find(reset($cmdParts));

            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}
