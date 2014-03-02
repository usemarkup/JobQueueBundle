<?php

namespace Markup\JobQueueBundle\Service;

use Markup\JobQueueBundle\Service\JobManager;
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
    public function writeConfig($uniqueEnvironment)
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

        $supervisordConfigFilePath = sprintf('%s/queue_%s_%s_generated.conf', $supervisordConfigPath, $resquePrefix, $uniqueEnvironment);

        $logger->info(sprintf('moving queue configuration to %s', $supervisordConfigFilePath));

        //copy the default supervisord_queues.conf.dist file to the destination path
        $finder = new Finder();
        $finder->name($defaultConfigFile)->in($kernelPath)->path('config')->depth(1);
        $results = iterator_to_array($finder);
        $file = current($results);
        copy($file->getRealPath(), $supervisordConfigFilePath);

        //stream "#{try_sudo} chmod 777 #{supervisor_config_file_path}"
        $logger->info('amending supervisor queue config');

        $queues = $this->jobby->getQueues();

        // write a configuration entry for each queue
        $queueGroups = [];
        foreach ($queues as $queueName) {
            $queueGroup = sprintf("phoenix_job_queue_%s_%s_%s", $resquePrefix, $uniqueEnvironment, $queueName);
            $queueGroups[] = $queueGroup;
            file_put_contents($supervisordConfigFilePath, "\n", FILE_APPEND);
            file_put_contents($supervisordConfigFilePath, sprintf("[program:%s]\n", $queueGroup), FILE_APPEND);
            file_put_contents($supervisordConfigFilePath, sprintf("command=%s %s/vendor/bcc/resque-bundle/BCC/ResqueBundle/bin/resque\n", $phpBin, $absoluteReleasePath), FILE_APPEND);
            file_put_contents($supervisordConfigFilePath, sprintf("user=%s\n", $supervisorUser), FILE_APPEND);
            file_put_contents($supervisordConfigFilePath, "autostart=false\n", FILE_APPEND);
            file_put_contents($supervisordConfigFilePath, "autorestart=true\n", FILE_APPEND);
            file_put_contents($supervisordConfigFilePath, sprintf("directory=%s\n", $absoluteReleasePath), FILE_APPEND);
            file_put_contents($supervisordConfigFilePath, "stopsignal=QUIT\n", FILE_APPEND);
            file_put_contents($supervisordConfigFilePath, sprintf("stderr_logfile=%s/logs/resque-worker.error.log\n", $kernelPath), FILE_APPEND);
            $envConfig = sprintf(
                "environment = APP_INCLUDE='%s/vendor/autoload.php',VERBOSE='1',QUEUE='%s',PREFIX='%s',COUNT='1',INTERVAL='5',REDIS_BACKEND='%s:%s',REDIS_BACKEND_DB='%s'",
                $absoluteReleasePath,
                $queueName,
                $resquePrefix,
                $redisHost,
                $redisPort,
                $redisDB
            );
            file_put_contents($supervisordConfigFilePath, $envConfig, FILE_APPEND);
            file_put_contents($supervisordConfigFilePath, "\n", FILE_APPEND);
        }

        // append queue groups
        file_put_contents($supervisordConfigFilePath, "\n", FILE_APPEND);
        file_put_contents($supervisordConfigFilePath, sprintf("[group:phoenix_%s_%s]\nprograms=%s", $resquePrefix, $uniqueEnvironment, implode(',', $queueGroups)), FILE_APPEND);

        // replace placeholders...
        $fileContents = implode("\n", file($supervisordConfigFilePath));
        $filePointer = fopen($supervisordConfigFilePath, 'w');
        $fileContents = str_replace('PHP_BIN_PLACEHOLDER', $phpBin, $fileContents);
        $fileContents = str_replace('RELEASE_PATH_PLACEHOLDER', $absoluteReleasePath, $fileContents);
        $fileContents = str_replace('USERNAME_PLACEHOLDER', $supervisorUser, $fileContents);
        $fileContents = str_replace('KERNEL_PATH_PLACEHOLDER', $kernelPath, $fileContents);
        fwrite($filePointer, $fileContents);
    }
}
