<?php

namespace Hub\Logger;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class LoggerHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('logger')) {
            return;
        }

        $definition = $container->findDefinition('logger');
        foreach ($container->findTaggedServiceIds('logger.handler') as $id => $tags) {
            $definition->addMethodCall('addHandler', [
                new Reference($id),
            ]);
        }
    }
}
