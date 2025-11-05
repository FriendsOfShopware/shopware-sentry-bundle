<?php

declare(strict_types=1);

namespace Frosh\SentryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('frosh_sentry');
        $rootNode = $treeBuilder->getRootNode();

        // @formatter:off
        $rootNode
            ->children()
                ->booleanNode('report_scheduled_tasks')->defaultFalse()->end()
                ->arrayNode('storefront')
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->arrayNode('replay_recording')
                            ->children()
                                ->booleanNode('enabled')->defaultFalse()->end()
                                ->floatNode('sample_rate')->defaultValue(0.1)->end()
                            ->end()
                        ->end()
                        ->arrayNode('tracing')
                            ->children()
                                ->booleanNode('enabled')->defaultFalse()->end()
                                ->floatNode('sample_rate')->defaultValue(0.1)->end()
                            ->end()
                        ->end()
                        ->scalarNode('javascript_sdk_version')->defaultValue('8.26.0')->end()
                    ->end()
            ->end();
        // @formatter:on

        return $treeBuilder;
    }
}
