<?php

namespace Markup\JobQueueBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Markup\JobQueueBundle\Exception\InvalidConfigurationException;
use Markup\JobQueueBundle\Model\RecurringConsoleComandConfiguration;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

/**
 * This class reads a defined configuration for recurring console commands and is able to provide information about those commands
 * Determining if the console command needs to be executed at the requested minute (in time).
 */
class RecurringConsoleCommandReader
{
    /**
     * The path and filename of the configuration file - relative to the application kernel
     * @var string
     */
    private $kernelPath;
    private $configurationFileName;
    private $configurations;

    public function __construct(
        $kernelPath
    ) {
        $this->kernelPath = $kernelPath;
        $this->configurationFileName = null;
        $this->configurations = null;
    }

    public function setConfigurationFileName($name)
    {
        $this->configurationFileName = $name;
    }

    /**
     * @return ArrayCollection<RecurringConsoleComandConfiguration>
     */
    public function getConfigurations()
    {
        if ($this->configurations !== null) {
            return $this->configurations;
        }
        if ($this->configurationFileName === null) {
            throw new \LogicException('Cannot get configurations as no config file has been defined');
        }
        $config = $this->getConfiguration();
        $configurations = $this->parseConfiguration($config);
        $this->configurations = $configurations;

        return $configurations;
    }

    /**
     * Gets any configurations which are due NOW and returns a collection of them
     * @return ArrayCollection<RecurringConsoleComandConfiguration>
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
     * Parses the configuration and returns an array of of configuration objects
     * Configuration is cached after running this function so it should only be run once
     * @param array $config
     */
    private function parseConfiguration(array $config)
    {
        $configurations = new ArrayCollection();
        foreach ($config as $pair) {
            if (!isset($pair['command']) || !isset($pair['schedule']) || !isset($pair['queue'])) {
                throw new InvalidConfigurationException('Every job schedule should have a `command`, `queue` and a `schedule` component');
            }
            //validate that the 'schedule' component is correct?

            $recurringConsoleComandConfiguration = new RecurringConsoleComandConfiguration($pair['command'], $pair['queue'], $pair['schedule']);

            if (isset($pair['timeout'])) {
                $recurringConsoleComandConfiguration->setTimeout($pair['timeout']);
            }
            $configurations->add($recurringConsoleComandConfiguration);
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
        $contents = $file->getContents();

        $config = $yamlParser->parse($contents);

        return $config;
    }
}
