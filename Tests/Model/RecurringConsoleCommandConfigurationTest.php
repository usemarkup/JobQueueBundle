<?php

namespace Markup\Bundle\JobQueueBundle\Tests\Service;

use Markup\JobQueueBundle\Model\RecurringConsoleCommandConfiguration;
use Mockery as m;

class RecurringConsoleCommandConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }

    public function testCanBeConstructed()
    {
        $config = new RecurringConsoleCommandConfiguration('foo:bar', 'test', '30 1 * * *');
        $this->assertEquals($config->getCommand(), 'foo:bar');
        $this->assertEquals($config->getTopic(), 'test');
        $this->assertEquals($config->getSchedule(), '30 1 * * *');
    }

    public function tearDown()
    {
        m::close();
    }
}
