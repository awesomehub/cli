<?php

declare(strict_types=1);

if (80000 > \PHP_VERSION_ID) {
    fwrite(
        \STDERR,
        'PHP 8.0 or later is needed to run this application.'.\PHP_EOL
    );

    exit(1);
}

// Set timezone if not set
ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

// Set error reporting
error_reporting(\E_ALL);

// Ensure errors are displayed correctly
// CLI - display errors only if they're not already logged to STDERR
if (function_exists('ini_set') && (!ini_get('log_errors') || ini_get('error_log'))) {
    ini_set('display_errors', '1');
}

// Find composer's autoload.php file
$loader = null;
foreach ([__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../autoload.php'] as $file) {
    if (file_exists($file)) {
        $loader = $file;

        break;
    }
}

// Check if project is not set up yet
if (null === $loader) {
    fwrite(
        \STDERR,
        'You need to set up the project dependencies using the following commands:'.\PHP_EOL.
        'wget https://getcomposer.org/composer.phar'.\PHP_EOL.
        'php composer.phar install'.\PHP_EOL
    );

    exit(1);
}

// Include composer autoload file
return include $loader;
