<?php

namespace Markup\JobQueueBundle\Service;

use Psr\Log\LoggerInterface;

class SupervisordConfigFileWriter
{
    /**
     * @var string
     */
    private $supervisordConfigPath;

    /**
     * @var LoggerInterface
     */
    private $logger;

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
    private $consumerCommandName;

    /**
     * SupervisordConfigFileWriter constructor.
     *
     * @param LoggerInterface $logger
     * @param                 $kernelPath
     * @param                 $kernelEnv
     */
    public function __construct(
        LoggerInterface $logger,
        $kernelPath,
        $kernelEnv
    ) {
        $this->logger = $logger;
        $this->kernelPath = $kernelPath;
        $this->kernelEnv = $kernelEnv;
    }

    /**
     * Sets the path to write the configuration file to
     */
    public function setSupervisordConfigPath($supervisordConfigPath)
    {
        $this->supervisordConfigPath = $supervisordConfigPath;
    }

    /**
     * Sets configuration for topics (in order to configure consumers)
     */
    public function setTopicsConfiguration($topics)
    {
        $this->topics = $topics;
    }

    /**
     * @param $consumerCommandName
     */
    public function setConsumerCommandName($consumerCommandName)
    {
        $this->consumerCommandName = $consumerCommandName;
    }

    /**
     * Writes the supervisord config file
     */
    public function writeConfig($uniqueEnvironment)
    {
        if (!$this->supervisordConfigPath) {
            throw new \Exception(
                sprintf('You must configure the supervisord config writer before writing a configuration file')
            );
        }

        $supervisordConfigPath = $this->supervisordConfigPath;
        $kernelPath = realpath($this->kernelPath);
        $absoluteReleasePath = realpath($kernelPath.'/..');

        $supervisordConfigFilePath = sprintf('%s/%s_programs.conf', $supervisordConfigPath, $uniqueEnvironment);

        // write a configuration entry for each queue
        $programNames = [];
        file_put_contents($supervisordConfigFilePath, '');
        foreach ($this->topics as $topic => $topicConfig) {

            //number of jobs to run before restarting...
            $programName = sprintf("markup_job_queue_%s_%s", $uniqueEnvironment, $topic);
            $programNames[] = $programName;
            $consumerCommand = sprintf(
                '%s/console %s -m %s %s -e=%s --no-debug',
                $this->consumerCommandName,
                $this->kernelPath,
                $topicConfig['consumption_quantity'],
                $topic,
                $this->kernelEnv
            );
            $conf = [];
            $conf[] = "\n";
            $conf[] = sprintf("[program:%s]", $programName);
            $conf[] = sprintf("command=%s", $consumerCommand);
            //$conf[] = sprintf("user=%s", $supervisorUser);
            $conf[] = sprintf("directory=%s", $absoluteReleasePath);
            $conf[] = sprintf("stderr_logfile=%s/logs/supervisord.error.log", $kernelPath);
            $conf[] = sprintf("stdout_logfile=%s/logs/supervisord.out.log", $kernelPath);
            $conf[] = "autostart=false";
            $conf[] = "autorestart=true";
            $conf[] = "stopsignal=QUIT";
            $conf[] = "\n";
            file_put_contents($supervisordConfigFilePath, implode("\n", $conf), FILE_APPEND);
        }

        // append 'group' of these consumers
        file_put_contents($supervisordConfigFilePath, "\n", FILE_APPEND);
        file_put_contents(
            $supervisordConfigFilePath,
            sprintf("[group:markup_%s]\nprograms=%s", $uniqueEnvironment, implode(',', $programNames)),
            FILE_APPEND
        );
    }
}
