<?php
# Bootstrap our app
require __DIR__ . '/bootstrap.php';

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Hub\Util\OutputFormatter;
use Hub\Logger\LoggerManager;
use Hub\Logger\Handler\ConsoleLoggerHandler;
use Hub\Logger\Handler\StreamLoggerHandler;
use Hub\Exception\ExceptionHandlerManager;
use Hub\Exception\Handler\LoggerExceptionHandler;
use Hub\Process\ProcessFactory;
use Hub\Filesystem\Filesystem;
use Hub\Application;
use Hub\Container;

$output = new ConsoleOutput(
    OutputInterface::VERBOSITY_NORMAL,
    null,
    new OutputFormatter()
);

$logger = new LoggerManager([
    new ConsoleLoggerHandler($output),
    new StreamLoggerHandler(__DIR__ . '/../../var/log/hub.log'),
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
