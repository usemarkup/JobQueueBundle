<?php

namespace Markup\Bundle\JobQueueBundle\Tests\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use Markup\JobQueueBundle\Entity\ScheduledJob;
use Markup\JobQueueBundle\Job\ConsoleCommandJob;
use Markup\JobQueueBundle\Job\SleepJob;
use Markup\JobQueueBundle\Service\JobManager;
use Markup\JobQueueBundle\Service\ScheduledJobService;
use Mockery as m;
use Markup\JobQueueBundle\Model\ScheduledJobRepositoryInterface;

class ScheduledJobServiceTest extends \PHPUnit_Framework_TestCase
{
    private $doctrine;

    public function setUp()
    {

        $scheduledJobRepository = m::mock(ScheduledJobRepositoryInterface::class);
        $scheduledJobRepository->shouldReceive('save');

        $this->doctrine = m::mock(ManagerRegistry::class);
        $this->doctrine->shouldReceive('getRepository')->andReturn($scheduledJobRepository);

        $this->scheduledJobService = new ScheduledJobService($this->doctrine);
    }

    public function testCanAddScheduledJob()
    {
        $scheduledTime = new \DateTime();
        $args = [];
        $args['command'] = 'command:test';
        $args['timeout'] = '60';
        $args['idleTimeout'] = '60';
        $topic = 'topic';
        $job = new ConsoleCommandJob($args, $topic);

        $this->assertEquals($args['command'], $job->getCommand());

        $scheduledJob = new ScheduledJob($job->getCommand(), $scheduledTime, $topic);

        $this->assertEquals($this->scheduledJobService->addScheduledJob($job, $scheduledTime), $scheduledJob);
    }

    public function tearDown()
    {
        m::close();
    }
}
