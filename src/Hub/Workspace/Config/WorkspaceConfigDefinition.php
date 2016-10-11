<?php
namespace Hub\Workspace\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class WorkspaceConfigDefinition implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('workspace');

        $rootNode
            ->children()
                ->arrayNode('github')
                    ->info('Contains github related configurations.')
                    ->isRequired()
                    ->children()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}