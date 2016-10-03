<?php
# Bootstrap our app
require __DIR__ . '/bootstrap.php';

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Docklyn\Util\OutputFormatter;
use Docklyn\Logger\LoggerManager;
use Docklyn\Logger\Handler\ConsoleLoggerHandler;
use Docklyn\Logger\Handler\StreamLoggerHandler;
use Docklyn\Exception\ExceptionHandlerManager;
use Docklyn\Exception\Handler\LoggerExceptionHandler;
use Docklyn\Process\ProcessFactory;
use Docklyn\Filesystem\Filesystem;
use Docklyn\Application;
use Docklyn\Container;

$output = new ConsoleOutput(
    OutputInterface::VERBOSITY_NORMAL,
    null,
    new OutputFormatter()
);

$logger = new LoggerManager([
    new ConsoleLoggerHandler($output),
    new StreamLoggerHandler(__DIR__ . '/../../var/log/docklyn.log'),
]);

$exception_handler = ExceptionHandlerManager::register([
    new LoggerExceptionHandler($logger)
]);

$container = new Container();
$container->setExceptionHandler($exception_handler);
$container->setApplication(new Application($container));
$container->setInput(new ArgvInput());
$container->setOutput($output);
$container->setLogger($logger);
$container->setProcessFactory(new ProcessFactory($logger));
$container->setFilesystem(new Filesystem());

# Run our cli app
$container->getApplication()->run();
