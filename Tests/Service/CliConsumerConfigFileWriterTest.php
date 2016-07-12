<?php

namespace Markup\Bundle\JobQueueBundle\Tests\Service;

use Markup\JobQueueBundle\Service\CliConsumerConfigFileWriter;
use Mockery as m;

class CliConsumerConfigFileWriterTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    public function testCreatesCorrectConfigString()
    {
        $writer = new CliConsumerConfigFileWriter(
            '/var/log/rabbitmq-cli-consumer',
            '/etc/rabbitmq-cli-consumer/config',
            'localhost',
            'test',
            'test',
            'test',
            '5672'
        );

        $fixture = file_get_contents(__DIR__ . '/fixtures/rabbitmq-cli-consumer-config.conf');
        $fixture = rtrim($fixture);
        $config = $writer->getConfigString('dev', 'indexing', ['prefetch_count' => 1]);

        $this->assertEquals($fixture, $config);
    }

    public function tearDown()
    {
        m::close();
    }
}
