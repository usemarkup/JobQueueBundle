<?php

namespace Markup\Bundle\JobQueueBundle\DependencyInjection;

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
        //
        $rootNode
            ->children()
                ->arrayNode('queues')
                    ->isRequired()
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('recurring')
                    ->validate()
                        ->ifTrue(function ($v) {
                            //check that the file has a .yml extension
                            return (strpos($v, '.yml') === false);
                        })->thenInvalid('Recurring Console Command configuration must be in .yml format')->end()
                    ->defaultFalse()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
