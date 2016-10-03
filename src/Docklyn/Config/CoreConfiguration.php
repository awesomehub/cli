<?php
namespace Docklyn\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class CoreConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('core');

        $rootNode
            ->children()
                ->scalarNode('log')
                    ->info('The path to the Docklyn log file.')
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue($this->isNotWritable())
                            ->thenInvalid('Invalid log file "%s".')
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    protected function isNotWritable()
    {
        return function ($path) {
            return !is_writable(dirname($path)) || !touch($path);
        };
    }
}