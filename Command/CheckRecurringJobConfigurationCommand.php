<?php

namespace Markup\JobQueueBundle\Command;

use Markup\JobQueueBundle\Exception\InvalidConfigurationException;
use Markup\JobQueueBundle\Service\RecurringConsoleCommandReader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckRecurringJobConfigurationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('markup:job_queue:recurring:check')
            ->setDescription('Checks the recurring job config files for validity.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reader = $this->getContainer()->get('markup_job_queue.reader.recurring_console_command');
        /**
         * @var RecurringConsoleCommandReader $reader
         */
        try {
            $reader->getConfigurations();
            $isGood = true;
        } catch (InvalidConfigurationException $e) {
            $isGood = false;
        }
        if ($isGood) {
            $output->writeln('<info>Recurring jobs config is good.</info>');

            return 0;
        } else {
            $output->writeln(sprintf('<error>Recurring jobs config is invalid. Message: %s</error>', $e->getMessage()));

            return 1;
        }
    }
}
