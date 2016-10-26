<?php
namespace Hub\Exception;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * DI Compiler Pass to register exception handlers.
 *
 * @package Hub\Exception
 */
class ExceptionHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('exception')) {
            return;
        }

        $definition = $container->findDefinition('exception');
        $handlers = [];
        foreach ($container->findTaggedServiceIds('exception.handler') as $id => $tags) {
            $handlers[] = new Reference($id);
        }

        // Overwrite previous handlers
        if(count($handlers)){
            $definition->addMethodCall('setHandlers', [$handlers]);
        }
    }
}
