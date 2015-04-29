<?php

namespace Markup\JobQueueBundle\Tests\Publisher;

use Markup\JobQueueBundle\Job\BadJob;
use Markup\JobQueueBundle\Publisher\JobPublisher;
use Mockery as m;
use Psr\Log\NullLogger;

class JobPublisherTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->producer = m::mock('SimpleBus\RabbitMQBundle\RabbitMQPublisher');
        $this->producer->shouldReceive('setContentType')->andReturn(null);
        $this->container = m::mock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->shouldReceive('has')->with('old_sound_rabbit_mq.test_producer')->andReturn(true);
        $this->container->shouldReceive('get')->with('old_sound_rabbit_mq.test_producer')->andReturn($this->producer);
        $this->container->shouldReceive('has')->with('old_sound_rabbit_mq.nonsense_producer')->andReturn(false);
        $this->container->shouldReceive('get')->with('logger')->andReturn(new NullLogger());
    }

    public function testPublishingJobWithInvalidTopicThrowsException()
    {
        $this->setExpectedException('Markup\JobQueueBundle\Exception\UndefinedProducerException');
        $job = new BadJob([], 'nonsense');
        $publisher = new JobPublisher();
        $publisher->setContainer($this->container);
        $publisher->publish($job);
    }

    public function testCanPublish()
    {
        $job = new BadJob([], 'test');
        $publisher = new JobPublisher();
        $publisher->setContainer($this->container);
        $this->producer->shouldReceive('publish')->once()->andReturn(null);
        $publisher->publish($job);
    }

    public function tearDown()
    {
        m::close();
    }
}
