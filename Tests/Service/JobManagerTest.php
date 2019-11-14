<?php

namespace Markup\Bundle\JobQueueBundle\Tests\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use Markup\JobQueueBundle\Job\SleepJob;
use Markup\JobQueueBundle\Model\Job;
use Markup\JobQueueBundle\Publisher\JobPublisher;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use Markup\JobQueueBundle\Service\JobManager;
use Markup\JobQueueBundle\Service\ScheduledJobService;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Error\Error;

class JobManagerTest extends MockeryTestCase
{
    /**
     * @var JobPublisher
     */
    private $jobPublisher;

    /**
     * @var ScheduledJobService
     */
    private $scheduledJobService;

    /**
     * @var JobManager
     */
    private $jobManager;

    protected function setUp()
    {
        $this->jobPublisher = $this->createStoringJobPublisher();
        $this->scheduledJobService = $this->createStoringJobScheduler();
        $this->jobManager = new JobManager(
            $this->jobPublisher,
            $this->scheduledJobService
        );
    }

    public function testCanAddJob()
    {
        $job = new SleepJob();
        $scheduledTime = new \DateTime();
        $this->jobManager->addJob($job);
        $this->jobManager->addJob($job, $scheduledTime);
        $this->assertSame([$job], $this->jobPublisher->getJobs());
        $this->assertSame([$job], $this->scheduledJobService->getJobs());
    }

    public function testCanAddCommandJob()
    {
        $this->jobManager->addCommandJob('console:herp:derp', 'system', 60, 60);
        $this->assertCount(1, $this->jobPublisher->getJobs());
    }

    public function testIdleTimeoutDefaultsToTimeout()
    {
        $timeout = 720;
        $this->jobManager->addCommandJob('command', 'topic', $timeout);
        /** @var Job $job */
        $job = $this->jobPublisher->getJobs()[0];
        $this->assertEquals($timeout, $job->getArgs()['idleTimeout']);
    }

    private function createStoringJobPublisher(): JobPublisher
    {
        return new class () extends JobPublisher {
            use JobStore;

            public function __construct()
            {
                parent::__construct(m::mock(JobLogRepository::class));
                $this->initializeJobs();
            }

            public function publish(Job $job)
            {
                $this->addJob($job);
            }
        };
    }

    private function createStoringJobScheduler()
    {
        return new class () extends ScheduledJobService {
            use JobStore;

            public function __construct()
            {
                parent::__construct(m::mock(ManagerRegistry::class));
                $this->initializeJobs();
            }

            public function addScheduledJob(Job $job, $scheduledTime)
            {
                $this->addJob($job);
            }
        };
    }
}

trait JobStore {
    /**
     * @var array<Job>
     */
    private $jobs;

    private function initializeJobs(): void
    {
        $this->jobs = [];
    }

    private function addJob(Job $job): void
    {
        $this->jobs[] = $job;
    }

    public function getJobs(): array
    {
        return $this->jobs;
    }
}
