<?php
declare(strict_types=1);

namespace Hub\Workspace\Config;

use Github\Utils\GithubTokenFactory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class WorkspaceConfigDefinition implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('config');

        $rootNode
            ->children()
                ->arrayNode('github')
                    ->info('Contains github related configurations.')
                    ->isRequired()
                    ->children()
                        ->arrayNode('tokens')
                            ->info('Contains github tokens for authentication.')
                            ->isRequired()
                            ->requiresAtLeastOneElement()
                            ->prototype('array')
                                ->children()
                                    ->enumNode('0')
                                        ->isRequired()
                                        ->values(GithubTokenFactory::supports())
                                    ->end()
                                    ->scalarNode('1')
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('2')
                                        ->cannotBeEmpty()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('dist')
                    ->info('Contains build related configurations.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('listCollections')
                            ->defaultValue([])
                            ->prototype('array')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
