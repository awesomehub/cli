<?php
// Bootstrap our app
require __DIR__.'/bootstrap.php';

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Http\Discovery\HttpClientDiscovery;
use Hub\Util\OutputFormatter;
use Hub\Logger\LoggerManager;
use Hub\Logger\Handler\ConsoleLoggerHandler;
use Hub\Logger\Handler\StreamLoggerHandler;
use Hub\Exception\ExceptionHandlerManager;
use Hub\Exception\Handler\LoggerExceptionHandler;
use Hub\Process\ProcessFactory;
use Hub\Filesystem\Filesystem;
use Hub\Environment\Environment;
use Hub\Application;
use Hub\Container;

$input = new ArgvInput();

$environment = new Environment($input);

$output = new ConsoleOutput(
    $environment->isDevelopment()
        ? OutputInterface::VERBOSITY_VERBOSE
        : OutputInterface::VERBOSITY_NORMAL,
    null,
    new OutputFormatter()
);

$logger = new LoggerManager([
    new ConsoleLoggerHandler($output),
    new StreamLoggerHandler($environment->getWorkspace()->get('hub.log')),
]);

$exception_handler = ExceptionHandlerManager::register([
    new LoggerExceptionHandler($logger),
]);

$container = new Container();
$container->setExceptionHandler($exception_handler);
$container->setApplication(new Application($container));
$container->setEnvironment($environment);
$container->setInput($input);
$container->setOutput($output);
$container->setStyle(new SymfonyStyle($input, $output));
$container->setLogger($logger);
$container->setHttp(HttpClientDiscovery::find());
$container->setProcessFactory(new ProcessFactory($logger));
$container->setFilesystem(new Filesystem());

// Run our app
$container->getApplication()->run();
