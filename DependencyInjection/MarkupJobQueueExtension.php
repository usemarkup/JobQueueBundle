<?php

namespace Markup\Bundle\JobQueueBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

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

        $this->registerAndValidateRecurringConfigurationFile($config, $container);
        $this->addQueuesToJobManager($config, $container);
    }

    /**
     * Stores the configured .yml file for use by the recurring console command reader
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerAndValidateRecurringConfigurationFile(array $config, ContainerBuilder $container)
    {
        if ($config['recurring'] !== false) {
            $recurringConsoleCommandReader = $container->getDefinition('markup_admin_job_queue_recurring_console_command_reader');
            $recurringConsoleCommandReader->addMethodCall('setConfigurationFileName', [$config['recurring']]);
        }
    }

    /**
     * Adds a list of allowed queues to the application - this should corespond to the workers that are processing jobs for those queues
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function addQueuesToJobManager(array $config, ContainerBuilder $container)
    {
        $queues = $config['queues'];
        $jobManager = $container->getDefinition('markup_admin_job_queue_manager');
        $jobManager->addMethodCall('setQueues', [$queues]);
    }
}
