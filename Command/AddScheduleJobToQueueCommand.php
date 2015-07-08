<?php

namespace Markup\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command adds scheduled jobs to the job-queue
 *
 */
class AddScheduleJobToQueueCommand extends ContainerAwareCommand
{

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('markup:scheduled_job:add')
            ->setDescription('Adds scheduled jobs to the job-queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scheduledJobService =  $this->getContainer()->get('markup_job_queue.scheduled');
        $logger = $this->getContainer()->get('logger');

        if ($jobs = $scheduledJobService->getUnqueuedJobs()) {
            foreach ($jobs as $job) {
                try {
                    $this->getContainer()->get('jobby')->addCommandJob(
                        $job->getJob(),
                        $job->getTopic(),
                        3600,
                        3600
                    );
                    $job->setQueued(true);
                    $scheduledJobService->save($job, $flush = true);
                } catch(\Exception $e) {
                    $logger->error(sprintf('There was an error adding the job "%s" to the job-queue, error: %s', $job->getJob(), $e->getMessage()));
                }
            }
        }
    }
}
