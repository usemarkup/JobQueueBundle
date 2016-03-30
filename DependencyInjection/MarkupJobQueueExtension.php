<?php

namespace Markup\JobQueueBundle\DependencyInjection;

use Markup\JobQueueBundle\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class MarkupJobQueueExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->registerRecurringConfigurationFile($config, $container);
        $this->addSupervisordConfig($config, $container);
    }

    /**
     * Stores the configured .yml file for use by the recurring console command reader
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerRecurringConfigurationFile(array $config, ContainerBuilder $container)
    {
        if ($config['recurring'] !== false) {
            $recurringConsoleCommandReader = $container->getDefinition('markup_admin_job_queue_recurring_console_command_reader');
            $recurringConsoleCommandReader->addMethodCall('setConfigurationFileName', [$config['recurring']]);
        }
    }

    /**
     * If Supervisord config variables have been set then set them against the container as parameters
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function addSupervisordConfig(array $config, ContainerBuilder $container)
    {
        $configFileWriter = $container->getDefinition('markup_job_queue.writer.supervisord_config_file');
        if (!$config['topics']) {
            throw new InvalidConfigurationException('markup_jop_queue requirea that at least 1 `topic` is configured');
        }
        $configFileWriter->addMethodCall('setSupervisordConfigPath', [$config['supervisor_config_path']]);
        $configFileWriter->addMethodCall('setTopicsConfiguration', [$config['topics']]);
    }
}
