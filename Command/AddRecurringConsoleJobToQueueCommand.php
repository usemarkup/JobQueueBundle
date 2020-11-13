<?php

namespace Markup\JobQueueBundle\Command;

use Markup\JobQueueBundle\Factory\JobStatusFactory;
use Markup\JobQueueBundle\Model\RecurringConsoleCommandConfiguration;
use Markup\JobQueueBundle\Repository\CronHealthRepository;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use Markup\JobQueueBundle\Service\JobManager;
use Markup\JobQueueBundle\Service\RecurringConsoleCommandReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command reads the recurring job configuration
 * and adds any recurring commands to the specified job queue
 * also performs maintenance on the recurring job log
 *
 * This command should be run every minute via crontab
 */
class AddRecurringConsoleJobToQueueCommand extends Command
{
    protected static $defaultName = 'markup:job_queue:recurring:add';

    /**
     * @var RecurringConsoleCommandReader
     */
    private $recurringConsoleCommandReader;

    /**
     * @var JobManager
     */
    private $jobManager;

    /**
     * @var CronHealthRepository
     */
    private $cronHealthRepository;

    /**
     * @var JobLogRepository
     */
    private $jobLogRepository;

    /**
     * @var string
     */
    private $environment;

    /** @var JobStatusFactory */
    private $jobStatusFactory;

    public function __construct(
        RecurringConsoleCommandReader $recurringConsoleCommandReader,
        JobManager $jobManager,
        CronHealthRepository $cronHealthRepository,
        JobLogRepository $jobLogRepository,
        JobStatusFactory $jobStatusFactory,
        string $environment
    ) {
        $this->recurringConsoleCommandReader = $recurringConsoleCommandReader;
        $this->jobManager = $jobManager;
        $this->cronHealthRepository = $cronHealthRepository;
        $this->jobLogRepository = $jobLogRepository;
        $this->jobStatusFactory = $jobStatusFactory;
        $this->environment = $environment;

        parent::__construct(null);
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
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
        $due = $this->recurringConsoleCommandReader->getDue();

        foreach ($due as $configuration) {
            if (!$configuration instanceof RecurringConsoleCommandConfiguration) {
                throw new \Exception('Invalid configuration');
            }

            if ($configuration->getEnvs()) {
                $env = $this->environment;

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

            $command = $configuration->getCommand();

            if (stripos($configuration->getCommand(), ' ') !== false) {
                throw new \LogicException(sprintf('%s Command cannot contain spaces', $configuration->getCommand()));
            }

            foreach ($configuration->getArguments() as $argument) {
                if (!is_string($argument)) {
                    throw new \Exception('Argument was expected to be a string');
                }

                $this->validateNoQuotes($argument, $configuration);

                if (substr($argument, 0, 2) === '--') {
                    $optionValue = ltrim(strstr($argument, '='), '=');

                    $this->validateNoQuotes($optionValue, $configuration);
                }
            }

            if ($configuration->getUserManaged() && !$this->jobStatusFactory->isUserEnabledJob($configuration->getCommand(), $configuration->getArguments())) {
                continue;
            }

            $this->jobManager->addConsoleCommandJob(
                $command,
                $configuration->getArguments(),
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

            $message = sprintf('%s. Will next be added %s', $message, $configuration->nextRun()->format('r'));
            $output->writeln(sprintf('<info>%s</info>', $message));
        }

        $this->cronHealthRepository->set();
    }

    private function maintainJobLogs()
    {
        $this->jobLogRepository->removeExpiredJobs();
    }

    /**
     * @param string $argument
     * @param RecurringConsoleCommandConfiguration $configuration
     * @throws \Exception
     */
    private function validateNoQuotes(string $argument, RecurringConsoleCommandConfiguration $configuration): void
    {
        $firstCharacter = substr($argument, 0, 1);
        $lastCharacter = substr($argument, strlen($argument)-1, 1);

        if ($firstCharacter === '"' && $lastCharacter === '"') {
            throw new \Exception(sprintf('remove quotes as they will be included as literal values on %s', $configuration->getCommand()));
        }

        if ($firstCharacter === "'" && $lastCharacter === "'") {
            throw new \Exception(sprintf('remove quotes as they will be included as literal values on %s', $configuration->getCommand()));
        }
    }
}
