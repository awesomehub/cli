<?php

declare(strict_types=1);

require __DIR__.'/bootstrap.php';

use Hub\ConsoleKernel;
use Hub\Exception\ExceptionHandlerManager;
use Hub\Exception\Handler\StartupExceptionHandler;
use Symfony\Component\Debug\ErrorHandler;

// Register exception manager and add a temporary startup exception handler
ExceptionHandlerManager::getInstance()
    ->addHandler(new StartupExceptionHandler())
    ->register()
;

// Use Symfony error handler to convert php errors to exceptions
ErrorHandler::register();

// Boot up our app
(new ConsoleKernel())->boot();
