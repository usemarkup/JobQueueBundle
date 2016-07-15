<?php

namespace Markup\JobQueueBundle\Tests\Publisher;

use Markup\JobQueueBundle\Job\BadJob;
use Markup\JobQueueBundle\Publisher\JobPublisher;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use Mockery as m;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;

class JobPublisherTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var JobLogRepository|m\MockInterface
     */
    private $jobLogRepository;

    /**
     * @var ProducerInterface|m\MockInterface
     */
    private $producer;

    public function setUp()
    {
        $this->producer = m::mock('SimpleBus\RabbitMQBundle\RabbitMQPublisher');
        $this->jobLogRepository = m::mock(JobLogRepository::class);
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
        $publisher = new JobPublisher($this->jobLogRepository, new NullLogger());
        $publisher->setContainer($this->container);
        $publisher->publish($job);
    }

    public function testCanPublish()
    {
        $job = new BadJob([], 'test');
        $publisher = new JobPublisher($this->jobLogRepository, new NullLogger());
        $publisher->setContainer($this->container);
        $this->producer->shouldReceive('publish')->once()->andReturn(null);
        $publisher->publish($job);
    }

    public function tearDown()
    {
        m::close();
    }
}
