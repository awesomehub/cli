<?php
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

$container = new ContainerBuilder();

$container
    ->setParameter('logger.stream.url', __DIR__ . '/../var/log/docklyn.log');

$container
    ->register('docklyn', 'Docklyn\Docklyn')
        ->addMethodCall('setExceptionHandler', [new Reference('docklyn.exception_handler')])
        ->addMethodCall('setApplication', [new Reference('docklyn.app')])
        ->addMethodCall('setInput', [new Reference('docklyn.input')])
        ->addMethodCall('setOutput', [new Reference('docklyn.output')])
        ->addMethodCall('setLogger', [new Reference('docklyn.logger')]);

$definition = (new Definition())
    ->setPublic(false)
    ->setFactory(['Docklyn\Exception\ExceptionHandlerManager', 'register'])
    ->addArgument([
        new Reference('docklyn.exception_handler.logger'),
    ]);
$container
    ->setDefinition('docklyn.exception_handler', $definition);

$container
    ->register('docklyn.exception_handler.logger', 'Docklyn\Exception\Handler\LoggerExceptionHandler')
        ->setPublic(false)
        ->addArgument(new Reference('docklyn.logger'));

$container
    ->register('docklyn.app', 'Docklyn\Application')
    ->setPublic(false)
        ->addArgument(new Reference('docklyn'));

$container
    ->register('docklyn.input', 'Symfony\Component\Console\Input\ArgvInput')
        ->setPublic(false);

$container
    ->register('docklyn.output', 'Symfony\Component\Console\Output\ConsoleOutput')
        ->setPublic(false)
        ->addArgument(Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL)
        ->addArgument(null)
        ->addArgument(new Reference('docklyn.output.formatter'));

$container
    ->register('docklyn.output.formatter', 'Docklyn\Util\OutputFormatter')
        ->setPublic(false);

$container
    ->register('docklyn.logger', 'Docklyn\Logger\LoggerManager')
        ->setPublic(false)
        ->addArgument([
            new Reference('docklyn.logger.console'),
            new Reference('docklyn.logger.stream'),
        ]);

$container
    ->register('docklyn.logger.console', 'Docklyn\Logger\Handler\ConsoleLoggerHandler')
        ->setPublic(false)
        ->addArgument(new Reference('docklyn.output'));

$container
    ->register('docklyn.logger.stream', 'Docklyn\Logger\Handler\StreamLoggerHandler')
        ->setPublic(false)
        ->addArgument('%logger.stream.url%');

unset($definition);

return $container;
