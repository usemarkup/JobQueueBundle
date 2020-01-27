<?php

namespace Markup\JobQueueBundle\Service;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Writes a config file suitable for use with:
 * https://github.com/ricbra/rabbitmq-cli-consumer#configuration
 */
class CliConsumerConfigFileWriter
{

    /**
     * @var string
     */
    private $logPath;

    /**
     * @var string
     */
    private $configFilePath;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $vhost;

    /**
     * @var string
     */
    private $port;

    /**
     * @var array
     */
    private $topics;

    /**
     * @param mixed $logPath
     * @param mixed $configFilePath
     * @param mixed $host
     * @param mixed $username
     * @param mixed $password
     * @param mixed $vhost
     * @param mixed $port
     */
    public function __construct(
        $logPath,
        $configFilePath,
        $host,
        $username,
        $password,
        $vhost,
        $port
    ) {
        $this->logPath = $logPath;
        $this->configFilePath = $configFilePath;
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->vhost = $vhost;
        $this->port = $port;
    }

    /**
     * Sets configuration for topics (in order to configure consumers)
     */
    public function setTopicsConfiguration(array $topics)
    {
        $this->topics = $topics;
    }

    /**
     * Writes the supervisord config file
     */
    public function writeConfig($uniqueEnvironment)
    {
        // make sure defined paths exist
        $fs = new Filesystem();
        if (!$fs->exists($this->logPath)) {
            throw new IOException(sprintf("%s does not exist, please create this folder", $this->logPath));
        }
        if (!$fs->exists($this->configFilePath)) {
            throw new IOException(sprintf("%s does not exist, please create this folder", $this->configFilePath));
        }

        // Write one CLI consumer config for each topic
        foreach ($this->topics as $topic => $topicConfig) {

            $cliConfigFile = sprintf(
                '%s/%s_%s_consumer.conf',
                $this->configFilePath,
                $uniqueEnvironment,
                $topic
            );

            $config = $this->getConfigString($uniqueEnvironment, $topic, $topicConfig);
            file_put_contents($cliConfigFile, $config);
        }
    }

    /**
     * @param mixed $uniqueEnvironment
     * @param mixed $topic
     * @param mixed $topicConfig
     * @return string
     */
    public function getConfigString($uniqueEnvironment, $topic, $topicConfig)
    {
        $conf = [];
        $conf[] = "[rabbitmq]";
        $conf[] = sprintf("host=%s", $this->host);
        $conf[] = sprintf("username=%s", $this->username);
        $conf[] = sprintf("password=%s", $this->password);
        $conf[] = sprintf("vhost=/%s", $this->vhost);
        $conf[] = sprintf("port=%s", $this->port);
        $conf[] = sprintf("queue=%s", $topic);
        $conf[] = "compression=Off";
        $conf[] = "";
        $conf[] = "[logs]";
        $conf[] = sprintf("error=%s", sprintf("%s/%s_%s_error.log", $this->logPath, $uniqueEnvironment, $topic));
        $conf[] = sprintf("info=%s", sprintf("%s/%s_%s_info.log", $this->logPath, $uniqueEnvironment, $topic));
        $conf[] = "";
        $conf[] = "[prefetch]";
        $conf[] = sprintf("count=%s", $topicConfig['prefetch_count']);
        $conf[] = "global=Off";
        $conf[] = "";
        $conf[] = "[exchange]";
        $conf[] = sprintf("name=%s", $topic);
        $conf[] = "autodelete=Off";
        $conf[] = "type=topic";
        $conf[] = "durable=On";

        return implode("\n", $conf);
    }
}
