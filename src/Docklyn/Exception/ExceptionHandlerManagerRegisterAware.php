<?php
namespace Docklyn\Exception;

use Docklyn\Exception\Handler\ExceptionHandlerInterface;

/**
 * Exception handler manager register-aware class.
 *
 * @package Docklyn
 */
abstract class ExceptionHandlerManagerRegisterAware implements ExceptionHandlerManagerInterface
{
    /**
     * @var ExceptionHandlerManagerInterface
     */
    protected static $instance;

    /**
     * @param ExceptionHandlerInterface[] $handlers
     * @return ExceptionHandlerManagerInterface
     */
    public static function register(array $handlers = [])
    {
        if (null === static::$instance) {
            static::$instance = new static();
            set_exception_handler([static::$instance, 'runHandlers']);
        }

        static::$instance->setHandlers($handlers);
        
        return static::$instance;
    }
}
