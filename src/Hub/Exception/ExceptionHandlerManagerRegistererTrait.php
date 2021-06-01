<?php

declare(strict_types=1);

namespace Hub\Exception;

use Symfony\Component\ErrorHandler\ErrorHandler;

/**
 * Exception handler manager registerer trait.
 */
trait ExceptionHandlerManagerRegistererTrait
{
    private static ?ExceptionHandlerManagerInterface $instance = null;

    /**
     * {@inheritdoc}
     */
    public function register(): ExceptionHandlerManagerInterface
    {
        $handler = [$this, 'runHandlers'];

        $prev = set_exception_handler($handler);

        // If Symfony ErrorHandler was active restore it and set the exception handler though it
        // This prevents some errors not get converted to exceptions
        if (\is_array($prev) && $prev[0] instanceof ErrorHandler) {
            restore_exception_handler();
            $prev[0]->setExceptionHandler($handler);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getInstance(): ExceptionHandlerManagerInterface
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
