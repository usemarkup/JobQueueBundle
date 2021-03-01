<?php

namespace Markup\Bundle\JobQueueBundle\Tests\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Markup\JobQueueBundle\Entity\Repository\ScheduledJobRepository;
use Markup\JobQueueBundle\Entity\ScheduledJob;
use Markup\JobQueueBundle\Job\ConsoleCommandJob;
use Markup\JobQueueBundle\Job\SleepJob;
use Markup\JobQueueBundle\Model\Job;
use Markup\JobQueueBundle\Publisher\JobPublisher;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use Markup\JobQueueBundle\Service\JobManager;
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
     * @var JobManager
     */
    private $jobManager;

    protected function setUp()
    {
        $this->jobPublisher = m::mock(JobPublisher::class);
        $this->jobManager = $this->createJobManager(
            $this->jobPublisher,
            $this->createScheduledJobRepositoryMock()
        );
    }

    public function testCanAddJobWithoutDateTime(): void
    {
        $job = new SleepJob();
        $this->jobManager->addJob($job);
        $this->assertSame([$job], $this->jobManager->getJobs());
    }

    public function testCanAddConsoleCommandJobWithDateTime(): void
    {
        $job = 'muh:console:jerb';
        $scheduledTime = new \DateTime();
        $this->jobManager->addScheduledConsoleCommandJob($job, $scheduledTime);
        $this->assertCount(1, $this->jobManager->getJobs());
    }

    public function testCanAddCommandJob(): void
    {
        $this->jobManager->addConsoleCommandJob('console:herp:derp', [], 'system', 60, 60);
        $this->assertCount(1, $this->jobManager->getJobs());
    }

    public function testIdleTimeoutDefaultsToTimeout(): void
    {
        $timeout = 720;
        $this->jobManager->addConsoleCommandJob('command', [], 'topic', $timeout);
        /** @var Job $job */
        $job = $this->jobManager->getJobs()[0];
        $this->assertEquals($timeout, $job->getArgs()['idleTimeout']);
    }
    
    private function createScheduledJobRepositoryMock()
    {
        return m::mock(ScheduledJobRepository::class)
            ->shouldReceive('save')
            ->andReturn(null)
            ->getMock();
    }
    
    private function createJobManager($jobPublisher, $scheduledJobRepository)
    {
        return new class ($jobPublisher, $scheduledJobRepository) extends JobManager {
            use JobStore;

            public function __construct(&$jobPublisher, $scheduledJobRepository)
            {
                parent::__construct($jobPublisher, $scheduledJobRepository);
            }

            public function addScheduledJob(ConsoleCommandJob $job, $scheduledTime): ScheduledJob
            {
                $this->addJob($job);
                return parent::addScheduledJob($job, $scheduledTime);
            }
        };
    }
}

trait JobStore {
    /**
     * @var array<Job>
     */
    private $jobs;

    public function addJob(Job $job, $supressLogging = false)
    {
        $this->jobs[] = $job;
    }

    public function getJobs(): array
    {
        return $this->jobs;
    }
}
