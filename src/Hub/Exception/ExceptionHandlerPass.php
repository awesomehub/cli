<?php

declare(strict_types=1);

namespace Hub\Exception;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * DI Compiler Pass to register exception handlers.
 */
class ExceptionHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('exception')) {
            return;
        }

        $definition = $container->findDefinition('exception');
        $handlers = [];
        foreach (array_keys($container->findTaggedServiceIds('exception.handler')) as $id) {
            $handlers[] = new Reference($id);
        }

        // Overwrite previous handlers
        if ([] !== $handlers) {
            $definition->addMethodCall('setHandlers', [$handlers]);
        }
    }
}
