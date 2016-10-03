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
use Docklyn\Docklyn;

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

$docklyn = new Docklyn();
$docklyn->setExceptionHandler($exception_handler);
$docklyn->setApplication(new Application($docklyn));
$docklyn->setInput(new ArgvInput());
$docklyn->setOutput($output);
$docklyn->setLogger($logger);
$docklyn->setProcessFactory(new ProcessFactory($logger));
$docklyn->setFilesystem(new Filesystem());

# Run our cli app
$docklyn->run();
