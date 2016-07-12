<?php

namespace Markup\JobQueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('markup_job_queue');

        // can specify a list of allowed queues
        // can specify a configuration file (.yml) that can be evaluated using cron syntax
        $rootNode
            ->children()
                ->arrayNode('topics')
                ->useAttributeAsKey('name')
                     ->prototype('array')
                         ->addDefaultsIfNotSet()
                         ->children()
                             ->integerNode('prefetch_count')
                                 ->defaultValue(1)
                                 ->min(1)
                             ->end()
                         ->end()
                     ->end()
                ->end()
                ->arrayNode('rabbitmq')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')
                            ->info('RabbitMQ host')
                            ->defaultValue('localhost')
                        ->end()
                        ->scalarNode('username')
                            ->info('RabbitMQ username')
                            ->defaultValue('guest')
                        ->end()
                        ->scalarNode('password')
                            ->info('RabbitMQ password')
                            ->defaultValue('guest')
                        ->end()
                        ->scalarNode('vhost')
                            ->info('RabbitMQ vhost')
                            ->defaultValue('/')
                        ->end()
                        ->scalarNode('port')
                            ->info('RabbitMQ port')
                            ->defaultValue('5672')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cli_consumer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->info('If enabled, the supervisord config writer will use the golang cli-consumer instead of the php consumer')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('log_path')
                            ->info('The path into which to place rabbit-cli-consumer log files')
                            ->defaultValue('/var/log/rabbitmq-cli-consumer')
                        ->end()
                        ->scalarNode('config_path')
                            ->info('The path into which to place rabbit-cli-consumer configuration files')
                            ->defaultValue('/etc/rabbit-cli-consumer/config')
                        ->end()
                        ->scalarNode('consumer_path')
                            ->info('The full path to the binary rabbit-cli-consumer')
                            ->defaultValue('/usr/local/bin/rabbitmq-cli-consumer')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('recurring')
                    ->info('The path to a .yml file containing configuration for recurring jobs')
                    ->validate()
                        ->ifTrue(function ($v) {
                            if ($v == false) {
                                return false;
                            }
                            //check that the file has a .yml extension
                            return (strpos($v, '.yml') === false);
                        })->thenInvalid('Recurring Console Command configuration must be in .yml format')->end()
                    ->defaultFalse()
                ->end()
                ->scalarNode('supervisor_config_path')
                    ->info('Path to store supervisord configuration files. Your supervisord configuration should load all files from this path')
                    ->defaultValue('/etc/supervisord/conf.d/')
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
