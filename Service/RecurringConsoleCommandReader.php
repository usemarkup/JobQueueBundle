<?php

namespace Markup\JobQueueBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Markup\JobQueueBundle\Exception\InvalidConfigurationException;
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
     * @return ArrayCollection<RecurringConsoleCommandConfiguration>
     * @throws InvalidConfigurationException
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
     * @param  string                                               $server
     * @return ArrayCollection<RecurringConsoleCommandConfiguration>
     */
    public function getDue($server)
    {
        $configurations = $this->getConfigurations();
        $due = new ArrayCollection();
        foreach ($configurations as $configuration) {
            if ($configuration->getServer() !== $server) {
                continue;
            }
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
            if (!isset($pair['command']) || !isset($pair['schedule']) || !isset($pair['queue']) || !isset($pair['server'])) {
                throw new InvalidConfigurationException('Every job schedule should have a `command`, `queue`, `schedule` and a `server` component');
            }
            //validate that the 'schedule' component is correct?

            $servers = $pair['server'];
            if (!is_array($servers)) {
                $servers = [$servers];
            }
            foreach ($servers as $server) {
                $recurringConsoleCommandConfiguration = new RecurringConsoleCommandConfiguration($pair['command'], $pair['queue'], $pair['schedule'], $server);
            }

            if (isset($pair['timeout'])) {
                $recurringConsoleCommandConfiguration->setTimeout($pair['timeout']);
            }
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
        /**
         * @var SplFileInfo $file
         */
        $contents = $file->getContents();

        try {
            $config = $yamlParser->parse($contents);
        } catch (ParseException $e) {
            throw new InvalidConfigurationException(sprintf('The job configuration file "%s" cannot be parsed.', $file->getRealPath()));
        }

        return $config;
    }
}
