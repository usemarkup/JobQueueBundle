<?php

namespace Markup\Bundle\JobQueueBundle\Tests\Service;

use Markup\JobQueueBundle\Job\SleepJob;
use Markup\JobQueueBundle\Publisher\JobPublisher;
use Markup\JobQueueBundle\Service\JobManager;
use Markup\JobQueueBundle\Service\ScheduledJobService;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Error\Error;

class JobManagerTest extends MockeryTestCase
{
    protected function setUp()
    {
        $jobPublisher = m::mock(JobPublisher::class);
        $scheduledJob = m::mock(ScheduledJobService::class);
        $jobPublisher->shouldReceive('publish')->andReturn(null);
        $scheduledJob->shouldReceive('addScheduledJob')->andReturn(null);
        $this->jobManager = new JobManager($jobPublisher, $scheduledJob);
    }

    public function testCanAddJob()
    {
        $job = new SleepJob();
        $scheduledTime = new \DateTime();
        $this->assertNull($this->jobManager->addJob($job));
        $this->assertNull($this->jobManager->addJob($job, $scheduledTime));
    }

    public function testDoesNotAcceptBadJobs()
    {
        $badjob = 'console:herp:derp';
        $exceptionThrown = false;
        try {
            $this->jobManager->addJob($badjob);
        } catch (Error $e) {
            $exceptionThrown = true;
        } catch (\TypeError $e) {
            $exceptionThrown = true;
        }
        if (!$exceptionThrown) {
            $this->fail();
        }
    }

    public function testCanAddCommandJob()
    {
        $this->assertNull($this->jobManager->addCommandJob('console:herp:derp', 'system', 60, 60));
    }
}
