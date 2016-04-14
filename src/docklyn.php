<?php
# Bootstrap our app
require __DIR__ . '/bootstrap.php';

use Symfony\Component\Debug\ErrorHandler;
use Docklyn\Exception\ExceptionHandlerManager;
use Docklyn\Exception\Handler\StartupExceptionHandler;

# Set error reporting
error_reporting(E_ALL);

# Ensure errors are displayed correctly
# CLI - display errors only if they're not already logged to STDERR
if (function_exists('ini_set') && (!ini_get('log_errors') || ini_get('error_log'))) {
    ini_set('display_errors', 1);
}

// register execption manager and add a temporary startup execption handler
// we also need to make sure the exception handler is registered before the error handler
ExceptionHandlerManager::register([new StartupExceptionHandler()]);

# Use symfony error handler to convert php errors to exceptions
ErrorHandler::register();

// Build the DI container
/* @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */
$container = require __DIR__ . '/services.php';

# Run our cli app
/* @var \Docklyn\Docklyn $docklyn */
$docklyn = $container->get('docklyn');
$docklyn->run();
