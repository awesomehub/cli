<?php
// Bootstrap our app
require __DIR__.'/bootstrap.php';

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Http\Adapter\Guzzle6\Client as GuzzleClient;
use Hub\Util\OutputFormatter;
use Hub\Logger\LoggerManager;
use Hub\Logger\Handler\ConsoleLoggerHandler;
use Hub\Logger\Handler\StreamLoggerHandler;
use Hub\Exception\ExceptionHandlerManager;
use Hub\Exception\Handler\LoggerExceptionHandler;
use Hub\Environment\Environment;
use Hub\Workspace\StartupWorkspace;
use Hub\Filesystem\Filesystem;
use Hub\Process\ProcessFactory;
use Hub\Application;
use Hub\Container;

$environment = new Environment();

$input = new ArgvInput();

$filesystem = new Filesystem();

$workspace = new StartupWorkspace($environment, $input, $filesystem);

$output = new ConsoleOutput(
    $environment->isDevelopment()
        ? OutputInterface::VERBOSITY_VERBOSE
        : OutputInterface::VERBOSITY_NORMAL,
    null,
    new OutputFormatter()
);

$logger = new LoggerManager([
    new ConsoleLoggerHandler($output),
    new StreamLoggerHandler($workspace->path('hub.log')),
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
$container->setHttp(new GuzzleClient());
$container->setProcessFactory(new ProcessFactory($logger));
$container->setWorkspace($workspace);
$container->setFilesystem($filesystem);

// Run our app
$container->getApplication()->run();
