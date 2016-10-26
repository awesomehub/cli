<?php

namespace Hub\EntryList;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Hub\EntryList\SourceProcessor\SourceProcessorInterface;

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
                            ->enumNode('type')
                                ->isRequired()
                                ->values(SourceProcessorInterface::SUPPORTS)
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
                    ->children()
                        ->arrayNode('githubRepo')
                            ->info('An optional github repo for the list.')
                            ->defaultValue([])
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('author')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('name')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('categoryTree')
                            ->info('An optional category tree to applied to the generated categories.')
                            ->defaultValue([])
                            ->prototype('variable')->end()
                            ->validate()
                            ->ifTrue(function ($tree) {
                                return $this->checkCategoryTreeDups($tree);
                            })
                                ->thenInvalid('Duplicate entries detected.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Validates category tree for possible duplicates.
     *
     * @param array $tree
     * @param int   $depth
     *
     * @throws \LogicException
     *
     * @return string|null
     */
    protected function checkCategoryTreeDups(array $tree, $depth = 0)
    {
        $categories = [];
        foreach ($tree as $parent => $child) {
            if (is_array($child)) {
                $categories[] = $parent;
                $categories   = array_merge($categories, $this->checkCategoryTreeDups($child, $depth + 1));
            } else {
                $categories[] = $child;
            }
        }

        if (0 === $depth) {
            $categoriesDup = array_unique(array_diff_assoc($categories, array_unique($categories)));

            return sizeof($categoriesDup) > 0;
        }

        return $categories;
    }
}
