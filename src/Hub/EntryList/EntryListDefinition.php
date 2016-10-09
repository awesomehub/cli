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
                ->booleanNode('processed')
                    ->info('Whether the list is processed or not.')
                    ->defaultValue(false)
                ->end()
                ->booleanNode('resolved')
                    ->info('Whether the list is resolved or not.')
                    ->defaultValue(false)
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
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('options')
                    ->info('The list options.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('categoryTree')
                            ->info('An optional category tree to applied to the generated categories.')
                            ->defaultValue([])
                            ->prototype('variable')->end()
                            ->validate()
                            ->ifTrue(function ($tree) { return $this->checkCategoryTreeDups($tree); })
                                ->thenInvalid("Duplicate entries detected.")
                            ->end()
                        ->end()
                        ->arrayNode('categoryNames')
                            ->info('An optional list of category names to be applied to the generated categories.')
                            ->defaultValue([])
                            ->prototype('variable')->end()
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
     * @param int $depth
     * @return string|null
     * @throws \LogicException
     */
    protected function checkCategoryTreeDups(array $tree, $depth = 0)
    {
        $categories = [];
        foreach($tree as $parent => $child) {
            if (is_array($child)) {
                $categories[] = $parent;
                $categories = array_merge($categories, $this->checkCategoryTreeDups($child, $depth + 1));
            }
            else {
                $categories[] = $child;
            }
        }

        if(0 === $depth){
            $categoriesDup = array_unique(array_diff_assoc($categories, array_unique($categories)));
            return sizeof($categoriesDup) > 0;
        }

        return $categories;
    }
}