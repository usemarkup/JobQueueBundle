<?php

namespace Markup\Bundle\JobQueueBundle\Tests\Service;

use Markup\JobQueueBundle\Service\CliConsumerConfigFileWriter;
use PHPUnit\Framework\TestCase;

class CliConsumerConfigFileWriterTest extends TestCase
{
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
}
