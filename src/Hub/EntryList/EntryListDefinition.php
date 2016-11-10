<?php

namespace Hub\EntryList;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class EntryListDefinition implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('list');

        $rootNode
            ->children()
                ->scalarNode('id')
                    ->info('The id of the list.')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('name')
                    ->info('The name of the list.')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('desc')
                    ->info('The list description.')
                    ->defaultValue(null)
                ->end()
                ->integerNode('score')
                    ->info('The list score.')
                    ->defaultValue(0)
                ->end()
                ->arrayNode('sources')
                    ->info('The sources for the list contents.')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('type')
                                ->isRequired()
                            ->end()
                            ->variableNode('data')
                                ->isRequired()
                            ->end()
                            ->arrayNode('options')
                                ->defaultValue([])
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('options')
                    ->info('The list options.')
                    ->addDefaultsIfNotSet()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
