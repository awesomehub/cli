<?php
declare(strict_types=1);

namespace Hub\EntryList;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class EntryListDefinition implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('list');

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
                    ->defaultValue(20)
                ->end()
                ->arrayNode('sources')
                    ->info('The sources for the list contents.')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('type')
                                ->info('Source type')
                                ->isRequired()
                            ->end()
                            ->variableNode('data')
                                ->info('Source data')
                                ->isRequired()
                            ->end()
                            ->arrayNode('options')
                                ->info('Source options')
                                ->ignoreExtraKeys(false)
                                ->append($this->getSourceCategoryNode())
                                ->append($this->getSourceCategoriesNode())
                                ->append($this->getSourceExcludeNode())
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('options')
                    ->info('The list options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('source')
                            ->info('Global source options')
                            ->ignoreExtraKeys(false)
                            ->append($this->getSourceCategoryNode())
                            ->append($this->getSourceCategoriesNode())
                            ->append($this->getSourceExcludeNode())
                        ->end()
                        ->arrayNode('categoryOrder')
                            ->info('An array of categories to control categories order')
                            ->useAttributeAsKey('path')
                            ->normalizeKeys(false)
                            ->prototype('scalar')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Category node.
     */
    protected function getSourceCategoryNode(): NodeDefinition
    {
        return (new TreeBuilder())
            ->root('category', 'scalar')
                ->info('A single category to map all entries to it discarding any other category')
                ->cannotBeEmpty()
            ;
    }

    /**
     * Categories option node.
     */
    protected function getSourceCategoriesNode(): ArrayNodeDefinition
    {
        return (new TreeBuilder())
            ->root('categories')
                ->info('A map of category => regex pattern(s) to match against entry ids')
                ->useAttributeAsKey('name')
                ->normalizeKeys(false)
                ->prototype('variable')
            ->end()
            ;
    }

    /**
     * Exclude option node.
     */
    protected function getSourceExcludeNode(): ArrayNodeDefinition
    {
        return (new TreeBuilder())
            ->root('exclude')
                ->info('An array of regex patterns to match against entry ids')
                ->prototype('scalar')
            ->end()
            ;
    }
}
