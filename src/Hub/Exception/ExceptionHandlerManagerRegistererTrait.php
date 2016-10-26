<?php

namespace Hub\Exception;

use Symfony\Component\Debug\ErrorHandler;

/**
 * Exception handler manager registerer trit.
 */
trait ExceptionHandlerManagerRegistererTrait
{
    /**
     * @var ExceptionHandlerManagerInterface
     */
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $handler = [$this, 'runHandlers'];

        $prev = set_exception_handler($handler);

        // If Symfony ErrorHandler was active restore it and set the exception handler though it
        // This prevents some errors not get converted to exceptions
        if (is_array($prev) && $prev[0] instanceof ErrorHandler) {
            restore_exception_handler();
            $prev[0]->setExceptionHandler($handler);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
