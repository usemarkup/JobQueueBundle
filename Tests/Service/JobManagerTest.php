<?php

namespace Markup\Bundle\JobQueueBundle\Tests\Service;
use Markup\JobQueueBundle\Exception\UnknownServerException;
use Markup\JobQueueBundle\Job\Test\SleepJob;
use Markup\JobQueueBundle\Service\JobManager;
use Mockery as m;

class JobManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $resque = m::mock('BCC\ResqueBundle\Resque');
        $resque->shouldReceive('enqueue')->with(\Mockery::type('BCC\ResqueBundle\Job'))->andReturn(null);
        $this->jobManager = new JobManager($resque);
        $master = [
            [
                'name' => 'feeds',
                'count' => 1
            ],
            [
                'name' => 'system',
                'count' => 5
            ],
        ];
        $slave = [
            [
                'name' => 'email',
                'count' => 1
            ]
        ];
        $this->jobManager->setQueues(
            ['master' => $master, 'slave' => $slave]
        );
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

    public function testCannotAddCommandJobToInvalidQueue()
    {
        $this->setExpectedException('Markup\JobQueueBundle\Exception\UnknownQueueException');
        $this->jobManager->addCommandJob('console:herp:derp', 'default', 60, 60);
    }

    public function testCanGetValidQueue()
    {
        $this->assertSame($this->jobManager->getValidQueueName('feeds'), 'feeds-master');
        $this->assertSame($this->jobManager->getValidQueueName('email-slave'), 'email-slave');

    }

    public function testGetUnknownQueueThrowsException()
    {
        $this->setExpectedException('Markup\JobQueueBundle\Exception\UnknownQueueException');
        $this->assertFalse($this->jobManager->getValidQueueName('invalidqueue'));
    }

    public function testReturnsAllQueuesWhenNoServerSpecified()
    {
        $this->assertSame($this->jobManager->getQueueConfigurations()->count(), 3);
    }

    public function testReturnsFilteredQueuesWhenServerSpecified()
    {
        $this->assertSame($this->jobManager->getQueueConfigurations('master')->count(), 2);

    }

    public function testFilteredByUnknownServerThrowsException()
    {
        $this->setExpectedException('Markup\JobQueueBundle\Exception\UnknownServerException');
        $this->assertSame($this->jobManager->getQueueConfigurations('testy')->count(), 2);
    }

    public function tearDown()
    {
        m::close();
    }
}
