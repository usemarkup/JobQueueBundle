<?php

namespace Phoenix\Bundle\JobQueueBundle\Tests\Service;

use Mockery as m;
use Phoenix\Bundle\JobQueueBundle\Job\Test\SleepJob;
use Phoenix\Bundle\JobQueueBundle\Service\JobManager;

class PromotionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $resque = m::mock('BCC\ResqueBundle\Resque');
        $resque->shouldReceive('enqueue')->with(\Mockery::type('BCC\ResqueBundle\Job'))->andReturn(null);
        $this->jobManager = new JobManager($resque);
        $this->jobManager->setQueues(['test', 'validqueue2']);
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
        $this->assertNull($this->jobManager->addCommandJob('console:herp:derp', 'validqueue2', 60, 60));
    }

    public function testCannotAddCommandJobToInvalidQueue()
    {
        $this->setExpectedException('Phoenix\Bundle\JobQueueBundle\Exception\UnknownQueueException');
        $this->jobManager->addCommandJob('console:herp:derp', 'default', 60, 60);
    }

    public function testValidQueue()
    {
        $this->assertTrue($this->jobManager->isValidQueue('test'));
    }

    public function testInvalidQueue()
    {
        $this->assertFalse($this->jobManager->isValidQueue('invalidqueue'));
    }

    public function tearDown()
    {
        m::close();
    }
}
