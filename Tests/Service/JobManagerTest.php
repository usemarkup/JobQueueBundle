<?php

namespace Markup\Bundle\JobQueueBundle\Tests\Service;

use Markup\JobQueueBundle\Job\SleepJob;
use Markup\JobQueueBundle\Service\JobManager;
use Mockery as m;

class JobManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $jobPublisher = m::mock('Markup\JobQueueBundle\Publisher\JobPublisher');
        $scheduledJob = m::mock('Markup\JobQueueBundle\Service\ScheduledJobService');
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
        $this->setExpectedException('TypeError');
        $this->jobManager->addJob($badjob);
    }

    public function testCanAddCommandJob()
    {
        $this->assertNull($this->jobManager->addCommandJob('console:herp:derp', 'system', 60, 60));
    }

    public function tearDown()
    {
        m::close();
    }
}
