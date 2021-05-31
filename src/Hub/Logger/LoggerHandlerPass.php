<?php

declare(strict_types=1);

namespace Hub\Logger;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LoggerHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('logger')) {
            return;
        }

        $definition = $container->findDefinition('logger');
        foreach (array_keys($container->findTaggedServiceIds('logger.handler')) as $id) {
            $definition->addMethodCall('addHandler', [
                new Reference($id),
            ]);
        }
    }
}
