<?php

declare(strict_types=1);

namespace Dkron\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dkron');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('endpoints')
                    ->info('Dkron server endpoint URLs (e.g. ["http://127.0.0.1:8080"])')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(fn ($v) => [$v])
                    ->end()
                    ->prototype('scalar')->end()
                ->end()
                ->floatNode('timeout')
                    ->info('HTTP timeout in seconds')
                    ->defaultValue(10.0)
                ->end()
                ->arrayNode('headers')
                    ->info('Default headers to send with every request (e.g. Authorization)')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
                ->scalarNode('http_client')
                    ->info('Service ID of a custom Guzzle or PSR-18 ClientInterface')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
