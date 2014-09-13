<?php

namespace Markup\JobQueueBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

class SupervisordConfigFileWriter
{
    /**
     * @todo move to configuration?
     * The name of this file must be present in the app/config location
     */
    const DEFAULT_CONFIG_FILE = 'supervisord_queues.conf.dist';

    private $supervisordUser;
    private $supervisordConfigPath;

    public function __construct(
        LoggerInterface $logger,
        JobManager $jobby,
        $kernelPath,
        $resquePrefix,
        $redisHost,
        $redisPort,
        $redisDB
    ) {
        $this->logger = $logger;
        $this->jobby = $jobby;
        $this->kernelPath = $kernelPath;
        $this->resquePrefix = $resquePrefix;
        $this->redisHost = $redisHost;
        $this->redisPort = $redisPort;
        $this->redisDB = $redisDB;
    }

    /**
     * The user who will execute the supervisord queue
     */
    public function setSupervisordUser($supervisordUser)
    {
        $this->supervisordUser = $supervisordUser;
    }

    /**
     * Sets the path to write the configuration file to
     */
    public function setSupervisordConfigPath($supervisordConfigPath)
    {
        $this->supervisordConfigPath = $supervisordConfigPath;
    }

    /**
     * Writes the supervisord config file
     */
    public function writeConfig($uniqueEnvironment, $server)
    {
        if (!$this->supervisordUser || !$this->supervisordConfigPath) {
            throw new \Exception('You must configure the supervisord config writer before writing a configuration file');
        }

        $supervisorUser = $this->supervisordUser;
        $supervisordConfigPath = $this->supervisordConfigPath;
        $defaultConfigFile = self::DEFAULT_CONFIG_FILE;
        $phpBin = 'php';
        $kernelPath = realpath($this->kernelPath);
        $absoluteReleasePath = realpath($kernelPath . '/..');

        $logger = $this->logger;

        $resquePrefix = $this->resquePrefix;
        $redisHost = $this->redisHost;
        $redisPort = $this->redisPort;
        $redisDB = $this->redisDB;

        $supervisordConfigFilePath = sprintf('%s/queue_%s_%s_%s_generated.conf', $supervisordConfigPath, $resquePrefix, $uniqueEnvironment, $server);

        $logger->info(sprintf('writing queue configuration to %s, clearing previous file', $supervisordConfigFilePath));
        file_put_contents($supervisordConfigFilePath, '');

        //copy the default supervisord_queues.conf.dist file to the destination path
        // $finder = new Finder();
        // $finder->name($defaultConfigFile)->in($kernelPath)->path('config')->depth(1);
        // $results = iterator_to_array($finder);
        // if (count($results) == 0) {
        //     throw new \Exception(sprintf('Default config file %s not present in project, cannot write config file', $defaultConfigFile));
        // }
        // $file = current($results);

        // copy($file->getRealPath(), $supervisordConfigFilePath);
        // write the default sceduled worker config:

        $conf = [];
        $conf[] = sprintf("[program:markup_job_queue_%s_%s_%s_scheduled]", $resquePrefix, $uniqueEnvironment, $server);
        $conf[] = sprintf("command=%s %s/vendor/bcc/resque-bundle/BCC/ResqueBundle/bin/resque-scheduler", $phpBin, $absoluteReleasePath);
        $conf[] = sprintf("user=%s", $supervisorUser);
        $conf[] = "autostart=false";
        $conf[] = "autorestart=true";
        $conf[] = sprintf("directory=%s", $absoluteReleasePath);
        $conf[] = "stopsignal=QUIT";
        $conf[] = sprintf("stderr_logfile=%s/logs/resque-scheduledworker.stderror.log", $kernelPath);
        $conf[] = sprintf("stdout_logfile=%s/logs/resque-scheduledworker.stdout.log", $kernelPath);

        $envConfig = sprintf(
            "environment = APP_INCLUDE='%s/vendor/autoload.php',VERBOSE='1',PREFIX='%s',REDIS_BACKEND='%s:%s',REDIS_BACKEND_DB='%s',RESQUE_PHP='%s/vendor/chrisboulton/php-resque/lib/Resque.php'",
            $absoluteReleasePath,
            $resquePrefix,
            $redisHost,
            $redisPort,
            $redisDB,
            $absoluteReleasePath
        );
        $conf[] = $envConfig;
        file_put_contents($supervisordConfigFilePath, implode("\n", $conf), FILE_APPEND);

        //stream "#{try_sudo} chmod 777 #{supervisor_config_file_path}"
        $logger->info('amending supervisor queue config');

        $queues = $this->jobby->getQueues($server);

        // write a configuration entry for each queue
        $queueGroups = [];
        foreach ($queues as $queueName) {
            $queueName = sprintf('%s-%s', $queueName, $server);
            $queueGroup = sprintf("markup_job_queue_%s_%s_%s", $resquePrefix, $uniqueEnvironment, $queueName);
            $queueGroups[] = $queueGroup;
            $conf = [];
            $conf[] = "\n";
            $conf[] = sprintf("[program:%s]", $queueGroup);
            $conf[] = sprintf("command=%s %s/vendor/bcc/resque-bundle/BCC/ResqueBundle/bin/resque", $phpBin, $absoluteReleasePath);
            $conf[] = sprintf("user=%s", $supervisorUser);
            $conf[] = "autostart=false";
            $conf[] = "autorestart=true";
            $conf[] = sprintf("directory=%s", $absoluteReleasePath);
            $conf[] = "stopsignal=QUIT";
            $conf[] = sprintf("stderr_logfile=%s/logs/resque-worker.error.log", $kernelPath);
            $conf[] = sprintf("stdout_logfile=%s/logs/resque-worker.out.log", $kernelPath);
            $envConfig = sprintf(
                "environment = APP_INCLUDE='%s/vendor/autoload.php',VERBOSE='1',QUEUE='%s',PREFIX='%s',COUNT='1',INTERVAL='20',REDIS_BACKEND='%s:%s',REDIS_BACKEND_DB='%s'",
                $absoluteReleasePath,
                $queueName,
                $resquePrefix,
                $redisHost,
                $redisPort,
                $redisDB
            );
            $conf[] = $envConfig;
            $conf[] = "\n";
            file_put_contents($supervisordConfigFilePath, implode("\n", $conf), FILE_APPEND);
        }

        // append queue groups
        file_put_contents($supervisordConfigFilePath, "\n", FILE_APPEND);
        file_put_contents($supervisordConfigFilePath, sprintf("[group:markup_%s_%s_%s]\nprograms=%s", $resquePrefix, $uniqueEnvironment, $server, implode(',', $queueGroups)), FILE_APPEND);
    }
}
