<?php

namespace Markup\JobQueueBundle\Service;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class SupervisordConfigFileWriter
{
    const MODE_PHP = 'php';
    const MODE_CLI = 'cli';

    /**
     * @var string
     */
    private $supervisordConfigPath;

    /**
     * @var string
     */
    private $kernelPath;

    /**
     * @var string
     */
    private $kernelEnv;

    /**
     * @var array
     */
    private $topics;

    /**
     * @var string
     */
    private $consumerPath;

    /**
     * @var string
     */
    private $logsDir;

    private $configFilePath;

    /**
     * @var string
     */
    private $mode;

    public function __construct(
        $kernelPath,
        $logsDir,
        $kernelEnv,
        $supervisordConfigPath,
        $consumerPath,
        $configFilePath
    ) {
        $this->kernelPath = $kernelPath;
        $this->logsDir = $logsDir;
        $this->kernelEnv = $kernelEnv;
        $this->supervisordConfigPath = $supervisordConfigPath;
        $this->consumerPath = $consumerPath;
        $this->configFilePath = $configFilePath;
        $this->mode = self::MODE_PHP;
    }

    /**
     * @param string $mode a valid mode constant
     * @throws \Exception if an ainvalid mode supplied
     */
    public function setMode($mode)
    {
        if (!in_array($mode, [self::MODE_PHP, self::MODE_CLI])) {
            throw new \Exception(sprintf('Mode `%s` is invalid', $mode));
        }
        $this->mode = $mode;
    }

    /**
     * Sets configuration for topics (in order to configure consumers)
     */
    public function setTopicsConfiguration($topics)
    {
        $this->topics = $topics;
    }

    /**
     * Writes the supervisord config file, the format of the file output depends on the mode
     */
    public function writeConfig($uniqueEnvironment)
    {
        $fs = new Filesystem();
        if (!$fs->exists($this->supervisordConfigPath)) {
            throw new IOException(
                sprintf("%s does not exist, please create this folder", $this->supervisordConfigPath)
            );
        }

        $supervisordConfigFilePath = sprintf('%s/%s_programs.conf', $this->supervisordConfigPath, $uniqueEnvironment);

        if ($this->mode === self::MODE_CLI) {
            $conf = $this->getConfigForCliConsumer($uniqueEnvironment);
        } else {
            $conf = $this->getConfigForPhpConsumer($uniqueEnvironment);
        }

        file_put_contents($supervisordConfigFilePath, $conf);
    }

    /**
     * @param string $uniqueEnvironment  environment disambiguator
     * @param bool $skipExistsChecks If set skips FS checks for binary and config file
     * @return string
     */
    public function getConfigForCliConsumer($uniqueEnvironment, $skipExistsChecks = false)
    {
        // make sure consumer binary exists
        $fs = new Filesystem();
        if (!$skipExistsChecks && !$fs->exists($this->consumerPath)) {
            throw new IOException(
                sprintf("%s does not exist, please ensure the consumer binary is installed", $this->consumerPath)
            );
        }

        $kernelPath = $skipExistsChecks ? $this->kernelPath : realpath($this->kernelPath);

        // write a configuration entry for each queue
        $programNames = [];
        $conf = [];

        foreach ($this->topics as $topic => $topicConfig) {
            $programName = sprintf("markup_job_queue_%s_%s", $uniqueEnvironment, $topic);
            $programNames[] = $programName;

            $cliConfigFile = sprintf(
                '%s/%s_%s_consumer.conf',
                $this->configFilePath,
                $uniqueEnvironment,
                $topic
            );

            if (!$skipExistsChecks && !$fs->exists($cliConfigFile)) {
                throw new IOException(sprintf("%s does not exist, ensure consumer config file has been written before writing supervisor config", $cliConfigFile));
            }

            $consumer = sprintf(
                '%s -e "%s/console %s --strict-exit-code --env=%s --no-debug" -c %s -V -i --strict-exit-code',
                $this->consumerPath,
                $kernelPath,
                $topicConfig['consumer'],
                $this->kernelEnv,
                $cliConfigFile
            );

            $conf[] = "\n";
            $conf[] = sprintf("[program:%s]", $programName);
            $conf[] = sprintf("command=%s", $consumer);
            $conf[] = sprintf("stderr_logfile=%s/supervisord.error.log", $this->logsDir);
            $conf[] = sprintf("stdout_logfile=%s/supervisord.out.log", $this->logsDir);
            $conf[] = "autostart=false";
            $conf[] = "autorestart=true";
            $conf[] = "stopsignal=QUIT";
            $conf[] = "startsecs=0";
        }
        $conf[] = "\n";
        $conf[] = sprintf("[group:markup_%s]\nprograms=%s", $uniqueEnvironment, implode(',', $programNames));

        return implode("\n", $conf);
    }

    /**
     * @param string $uniqueEnvironment
     * @return string
     */
    public function getConfigForPhpConsumer($uniqueEnvironment, $skipExistsChecks = false)
    {
        $kernelPath = $skipExistsChecks ? $this->kernelPath : realpath($this->kernelPath);
        $absoluteReleasePath = $skipExistsChecks ? $kernelPath.'/..' : realpath($kernelPath.'/..');

        // write a configuration entry for each queue
        $programNames = [];
        $conf = [];

        foreach ($this->topics as $topic => $topicConfig) {
            //number of jobs to run before restarting...
            $programName = sprintf("markup_job_queue_%s_%s", $uniqueEnvironment, $topic);
            $programNames[] = $programName;
            $consumerCommand = sprintf(
                '%s/console %s -m %s %s --env=%s --no-debug',
                $this->kernelPath,
                'rabbitmq:consumer',
                $topicConfig['prefetch_count'],
                $topic,
                $this->kernelEnv
            );
            $conf[] = "\n";
            $conf[] = sprintf("[program:%s]", $programName);
            $conf[] = sprintf("command=%s", $consumerCommand);
            $conf[] = sprintf("directory=%s", $absoluteReleasePath);
            $conf[] = sprintf("stderr_logfile=%s/supervisord.error.log", $this->logsDir);
            $conf[] = sprintf("stdout_logfile=%s/supervisord.out.log", $this->logsDir);
            $conf[] = "autostart=false";
            $conf[] = "autorestart=true";
            $conf[] = "stopsignal=QUIT";
            $conf[] = "startsecs=0";
        }
        $conf[] = "\n";
        $conf[] = sprintf("[group:markup_%s]\nprograms=%s", $uniqueEnvironment, implode(',', $programNames));

        return implode("\n", $conf);
    }
}
