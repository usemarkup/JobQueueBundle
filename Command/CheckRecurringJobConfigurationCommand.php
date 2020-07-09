<?php

namespace Markup\JobQueueBundle\Command;

use Markup\JobQueueBundle\Exception\InvalidConfigurationException;
use Markup\JobQueueBundle\Service\RecurringConsoleCommandReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckRecurringJobConfigurationCommand extends Command
{
    protected static $defaultName = 'markup:job_queue:recurring:check';

    /**
     * @var RecurringConsoleCommandReader
     */
    private $recurringConsoleCommandReader;

    public function __construct(RecurringConsoleCommandReader $recurringConsoleCommandReader)
    {
        $this->recurringConsoleCommandReader = $recurringConsoleCommandReader;
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setDescription('Checks the recurring job config files for validity.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = '';
        /**
         * @var RecurringConsoleCommandReader $reader
         */
        try {
            $this->recurringConsoleCommandReader->getConfigurations();
            $isGood = true;
        } catch (InvalidConfigurationException $e) {
            $isGood = false;

            $message = $e->getMessage();
        }

        if ($isGood) {
            $output->writeln('<info>Recurring jobs config is good.</info>');

            return 0;
        } else {
            $output->writeln(sprintf('<error>Recurring jobs config is invalid. Message: %s</error>', $message));

            return 1;
        }
    }
}
