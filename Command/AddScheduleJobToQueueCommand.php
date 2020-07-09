<?php

namespace Markup\JobQueueBundle\Command;

use Markup\JobQueueBundle\Entity\ScheduledJob;
use Markup\JobQueueBundle\Service\JobManager;
use Markup\JobQueueBundle\Service\ScheduledJobService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command adds scheduled jobs to the job-queue
 *
 */
class AddScheduleJobToQueueCommand extends Command
{
    protected static $defaultName = 'markup:scheduled_job:add';

    /**
     * @var JobManager
     */
    private $jobManager;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var ScheduledJobService
     */
    private $scheduledJobService;

    public function __construct(
        JobManager $jobManager,
        ScheduledJobService $scheduledJobService,
        ?LoggerInterface $logger = null
    ) {
        $this->jobManager = $jobManager;
        $this->logger = $logger;
        $this->scheduledJobService = $scheduledJobService;

        parent::__construct(null);
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Adds scheduled jobs to the job-queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobs = $this->scheduledJobService->getUnqueuedJobs();

        if ($jobs) {
            foreach ($jobs as $job) {
                if (!$job instanceof ScheduledJob) {
                    continue;
                }

                try {
                    $this->jobManager->addConsoleCommandJob(
                        $job->getJob(),
                        $job->getArguments(),
                        $job->getTopic(),
                        3600,
                        3600
                    );
                    $job->setQueued(true);

                    $this->scheduledJobService->save($job, $flush = true);
                } catch (\Exception $e) {
                    $this->logger->error(
                        sprintf(
                            'There was an error adding the job "%s" to the job-queue, error: %s',
                            $job->getJob(),
                            $e->getMessage()
                        )
                    );
                }
            }
        }
    }
}
