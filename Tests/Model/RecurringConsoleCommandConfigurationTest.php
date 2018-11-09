<?php

namespace Markup\Bundle\JobQueueBundle\Tests\Service;

use Markup\JobQueueBundle\Model\RecurringConsoleCommandConfiguration;
use PHPUnit\Framework\TestCase;

class RecurringConsoleCommandConfigurationTest extends TestCase
{
    public function testCanBeConstructed()
    {
        $config = new RecurringConsoleCommandConfiguration('foo:bar', 'test', '30 1 * * *', 'a short description');
        $this->assertEquals($config->getCommand(), 'foo:bar');
        $this->assertEquals($config->getTopic(), 'test');
        $this->assertEquals($config->getSchedule(), '30 1 * * *');
        $this->assertEquals($config->getDescription(), 'a short description');
    }
}
