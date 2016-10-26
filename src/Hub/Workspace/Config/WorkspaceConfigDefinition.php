<?php
namespace Hub\Workspace\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Github\Utils\GithubTokenFactory;

class WorkspaceConfigDefinition implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
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
            ->end()
        ;

        return $treeBuilder;
    }
}