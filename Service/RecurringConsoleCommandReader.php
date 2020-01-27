<?php

namespace Markup\JobQueueBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Markup\JobQueueBundle\Exception\InvalidConfigurationException;
use Markup\JobQueueBundle\Exception\MissingScheduleException;
use Markup\JobQueueBundle\Exception\MissingConfigurationException;
use Markup\JobQueueBundle\Model\RecurringConsoleCommandConfiguration;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * This class reads a defined configuration for recurring console commands and is able to provide information about those commands
 * Determining if the console command needs to be executed at the requested minute (in time).
 */
class RecurringConsoleCommandReader
{
    /**
     * @var string
     */
    private $kernelPath;

    /**
     * @var string
     */
    private $configurationFileName;

    /**
     * @var ArrayCollection
     */
    private $configurations;

    /**
     * @param mixed $kernelPath string
     */
    public function __construct(
        $kernelPath
    ) {
        $this->kernelPath = $kernelPath;
    }

    public function setConfigurationFileName($name)
    {
        $this->configurationFileName = $name;
    }

    /**
     * @return ArrayCollection<RecurringConsoleCommandConfiguration>
     * @throws InvalidConfigurationException                         If any configuration is missing parameters
     * @throws MissingScheduleException                              If schedule file has not been configured
     */
    public function getConfigurations()
    {
        if ($this->configurations !== null) {
            return $this->configurations;
        }
        if ($this->configurationFileName === null) {
            throw new MissingScheduleException('Cannot get configurations as no config file has been defined');
        }
        $config = $this->getConfiguration();
        $configurations = $this->parseConfiguration($config);
        // cache for next lookup
        $this->configurations = $configurations;

        return $configurations;
    }

    /**
     * @param mixed $id
     */
    public function getConfigurationById($id)
    {
        foreach($this->getConfigurations() as $configuration) {
            if ($configuration->getUuid() === $id) {
                return $configuration;
            }
        }
        return null;
    }

    /**
     * Gets any configurations which are due NOW and returns a collection of them
     * @return ArrayCollection<RecurringConsoleCommandConfiguration>
     */
    public function getDue()
    {
        $configurations = $this->getConfigurations();
        $due = new ArrayCollection();
        foreach ($configurations as $configuration) {
            if ($configuration->isDue()) {
                $due->add($configuration);
            }
        }

        return $due;
    }

    /**
     * Gets an array of the distinct command strings used in recurring job configurations
     */
    public function getConfigurationCommands()
    {
        $commands = [];
        foreach($this->getConfigurations() as $configuration) {
            $key = hash('SHA256', $configuration->getCommand());
            $commands[$key] = $configuration->getCommand();
        }
        return $commands;
    }

    /**
     * Parses the configuration and returns an array of of configuration objects
     * Configuration is cached after running this function so it should only be run once
     *
     * @param array $config
     * @return ArrayCollection
     * @throws InvalidConfigurationException
     */
    private function parseConfiguration(array $config)
    {
        $configurations = new ArrayCollection();
        foreach ($config as $group) {
            if (!isset($group['command']) || !isset($group['schedule']) || !isset($group['topic'])) {
                throw new InvalidConfigurationException('Every job schedule should have a `command`, `topic` and `schedule` component'.json_encode($config));
            }

            if (isset($group['envs']) && !is_array($group['envs'])) {
                throw new InvalidConfigurationException('`envs` config key must be an array or null');
            }

            $recurringConsoleCommandConfiguration = new RecurringConsoleCommandConfiguration(
                $group['command'],
                $group['topic'],
                $group['schedule'],
                isset($group['description']) ? $group['description'] : null,
                isset($group['timeout']) ? $group['timeout'] : null,
                isset($group['envs']) ? $group['envs'] : null
            );

            $configurations->add($recurringConsoleCommandConfiguration);
        }

        return $configurations;
    }

    /**
     * Reads the configuration file using the yml component and returns an array
     * @return array
     */
    private function getConfiguration()
    {
        $yamlParser = new Parser();
        $finder = new Finder();
        $finder->name($this->configurationFileName)->in($this->kernelPath)->path('config')->depth(1);

        $results = iterator_to_array($finder);

        $file = current($results);
        if (false === $file) {
            throw new MissingConfigurationException(sprintf('A configuration file "%s" was expected to be found in %s.', $this->configurationFileName, $this->kernelPath . '/config'));
        }

        /**
         * @var SplFileInfo $file
         */
        $contents = $file->getContents();

        try {
            $config = $yamlParser->parse($contents);
        } catch (ParseException $e) {
            throw new InvalidConfigurationException(sprintf('The job configuration file "%s" cannot be parsed: %s', $file->getRealPath(), $e->getMessage()));
        }

        return $config;
    }
}
