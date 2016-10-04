<?php
# Check for php version
if (version_compare('7.0.0', PHP_VERSION, '>')) {
    fwrite(
        STDERR,
        'PHP 7.0 or later is needed to run this application.' . PHP_EOL
    );

    exit(1);
}

# Set timezone if not set
if (!ini_get('date.timezone')) {
    ini_set('date.timezone', 'UTC');
}

# Set error reporting
error_reporting(E_ALL);

# Ensure errors are displayed correctly
# CLI - display errors only if they're not already logged to STDERR
if (function_exists('ini_set') && (!ini_get('log_errors') || ini_get('error_log'))) {
    ini_set('display_errors', 1);
}

# Load Composer Aautoloader
require __DIR__ . '/loader.php';

use Symfony\Component\Debug\ErrorHandler;
use Hub\Exception\ExceptionHandlerManager;
use Hub\Exception\Handler\StartupExceptionHandler;

// Register execption manager and add a temporary startup execption handler
// We also need to make sure the exception handler is registered before the error handler
ExceptionHandlerManager::register([new StartupExceptionHandler()]);

# Use symfony error handler to convert php errors to exceptions
ErrorHandler::register();
