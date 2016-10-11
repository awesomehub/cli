<?php
// Bootstrap our app
require __DIR__.'/bootstrap.php';

use Symfony\Component\Debug\ErrorHandler;
use Hub\Exception\ExceptionHandlerManager;
use Hub\Exception\Handler\StartupExceptionHandler;
use Hub\Kernel;

// Register execption manager and add a temporary startup execption handler
// We also need to make sure the exception handler is registered before the error handler
ExceptionHandlerManager::getInstance()
    ->addHandler(new StartupExceptionHandler())
    ->register();

// Use symfony error handler to convert php errors to exceptions
ErrorHandler::register();

// Boot up our app
(new Kernel())->boot();
