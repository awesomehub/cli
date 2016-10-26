<?php
// Bootstrap our app
require __DIR__.'/bootstrap.php';

use Symfony\Component\Debug\ErrorHandler;
use Hub\Exception\ExceptionHandlerManager;
use Hub\Exception\Handler\StartupExceptionHandler;
use Hub\ConsoleKernel;

// Register execption manager and add a temporary startup execption handler
ExceptionHandlerManager::getInstance()
    ->addHandler(new StartupExceptionHandler())
    ->register();

// Use Symfony error handler to convert php errors to exceptions
ErrorHandler::register();

// Boot up our app
(new ConsoleKernel())->boot();
