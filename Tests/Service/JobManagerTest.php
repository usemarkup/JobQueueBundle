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
        $jobPublisher->shouldReceive('publish')->andReturn(null);
        $this->jobManager = new JobManager($jobPublisher);
    }

    public function testCanAddJob()
    {
        $job = new SleepJob();
        $this->assertNull($this->jobManager->addJob($job));
    }

    public function testDoesNotAcceptBadJobs()
    {
        $badjob = 'console:herp:derp';
        $this->setExpectedException('PHPUnit_Framework_Error');
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
