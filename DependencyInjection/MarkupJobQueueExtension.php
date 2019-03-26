<?php

namespace Markup\JobQueueBundle\DependencyInjection;

use Markup\JobQueueBundle\Exception\InvalidConfigurationException;
use Markup\JobQueueBundle\Service\SupervisordConfigFileWriter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;

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
        $loader->load('commands.yml');

        $this->registerRecurringConfigurationFile($config, $container);
        $this->addSupervisordConfig($config, $container);
        $this->addCliConsumerConfig($config, $container);
        $this->configureRabbitMqApiClient($config, $container);
        $this->configureJobLogRepository($config, $container);
        $this->configureConsoleDirectory($config, $container);
    }

    /**
     * Stores the configured .yml file for use by the recurring console command reader
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerRecurringConfigurationFile(array $config, ContainerBuilder $container)
    {
        if ($config['recurring'] !== false) {
            $recurringConsoleCommandReader = $container->getDefinition('markup_job_queue.reader.recurring_console_command');
            $recurringConsoleCommandReader->addMethodCall('setConfigurationFileName', [$config['recurring']]);
        }
    }

    /**
     * If Supervisord config variables have been set then set them against the container as parameters
     *
     * @param array $config
     * @param ContainerBuilder $container
     *
     * @throws InvalidConfigurationException
     */
    private function addSupervisordConfig(array $config, ContainerBuilder $container)
    {
        $configFileWriter = $container->getDefinition('markup_job_queue.writer.supervisord_config_file');
        if (!$config['topics']) {
            throw new InvalidConfigurationException('markup_job_queue requires that at least 1 `topic` is configured');
        }
        $configFileWriter->replaceArgument(3, $config['supervisor_config_path']);
        $configFileWriter->replaceArgument(4, $config['cli_consumer']['consumer_path']);
        $configFileWriter->replaceArgument(5, $config['cli_consumer']['config_path']);
        $configFileWriter->addMethodCall('setTopicsConfiguration', [$config['topics']]);

        if ($config['cli_consumer']['enabled'] === true) {
            $configFileWriter->addMethodCall('setMode', [SupervisordConfigFileWriter::MODE_CLI]);
        }
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     *
     * @throws InvalidConfigurationException
     */
    private function addCliConsumerConfig(array $config, ContainerBuilder $container)
    {
        $configFileWriter = $container->getDefinition('markup_job_queue.writer.cli_consumer_config_file');
        if (!$config['topics']) {
            throw new InvalidConfigurationException('markup_job_queue requires that at least 1 `topic` is configured');
        }

        $cliConsumerConfig = $config['cli_consumer'];
        $rabbitConfig = $config['rabbitmq'];

        $configFileWriter->setArguments([
            $cliConsumerConfig['log_path'],
            $cliConsumerConfig['config_path'],
            $rabbitConfig['host'],
            $rabbitConfig['username'],
            $rabbitConfig['password'],
            $rabbitConfig['vhost'],
            $rabbitConfig['port'],
        ]);
        $configFileWriter->addMethodCall('setTopicsConfiguration', [$config['topics']]);
    }

    /**
     * Configures the RabbitMQ Api client to allow api connection
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function configureRabbitMqApiClient(array $config, ContainerBuilder $container)
    {
        $rabbitMqClient = $container->getDefinition('markup_job_queue.rabbit_mq_api.client');
        $rabbitConfig = $config['rabbitmq'];

        $baseUrl = sprintf('http://%s:15672', $rabbitConfig['host']);
        $rabbitMqClient->setArguments([
            $baseUrl,
            $rabbitConfig['username'],
            $rabbitConfig['password'],
        ]);

        $queueReader = $container->getDefinition('markup_job_queue.reader.queue');

        $queueReader->replaceArgument(1, $rabbitConfig['vhost']);
    }

    private function configureJobLogRepository(array $config, ContainerBuilder $container)
    {
        $repository = $container->getDefinition('markup_job_queue.repository.job_log');
        $repository->addMethodCall('setTtl', [$config['job_logging_ttl']]);
        $repository->addMethodCall('setShouldClearLogForCompleteJob', [$config['clear_log_for_complete_jobs']]);
    }

    private function configureConsoleDirectory(array $config, ContainerBuilder $container)
    {
        $parameter = 'markup_job_queue.console_dir';
        $isUsingSymfony4OrGreater = Kernel::MAJOR_VERSION === 4;
        if ($isUsingSymfony4OrGreater && $config['use_root_dir_for_symfony_console']) {
            throw new \Exception('The `use_root_dir_for_symfony_console` option cannot be used with Symfony 4+.');
        }

        if ($config['use_root_dir_for_symfony_console']) {
            $container->setParameter($parameter, $container->getParameter('kernel.root_dir'));

            return;
        }

        $container->setParameter($parameter, $container->getParameter('kernel.project_dir') . '/bin');
    }
}
