<?php

namespace Markup\Bundle\JobQueueBundle\Tests\Service;

use Markup\JobQueueBundle\Service\SupervisordConfigFileWriter;
use PHPUnit\Framework\TestCase;

class SupervisordConfigFileWriterTest extends TestCase
{
    protected function setUp()
    {
        $this->writer = new SupervisordConfigFileWriter(
            '/vagrant/app',
            '/vagrant/app/logs',
            'dev',
            '/etc/supervisord/conf.d',
            '/usr/local/bin/rabbitmq-cli-consumer',
            '/etc/rabbitmq-cli-consumer/config'
        );

        $topicA = ['prefetch_count' => 1, 'consumer' => 'markup:job_queue:consumer'];
        $topicB = ['prefetch_count' => 2, 'consumer' => 'markup:job_queue:consumer'];
        $this->writer->setTopicsConfiguration(['testqueuea' => $topicA, 'testqueueb' => $topicB]);
    }

    public function testWritesCliConfiguration()
    {
        $fixture = file_get_contents(__DIR__ . '/fixtures/supervisord_config_cli.conf');
        $fixture = rtrim($fixture);
        $config = $this->writer->getConfigForCliConsumer('testenv', $skipCheck = true);

        $this->assertEquals($fixture, $config);
    }

    public function testWritesPhpConfiguration()
    {
        $fixture = file_get_contents(__DIR__ . '/fixtures/supervisord_config_php.conf');
        $fixture = rtrim($fixture);
        $config = $this->writer->getConfigForPhpConsumer('testenv', $skipCheck = true);

        $this->assertEquals($fixture, $config);
    }
}
