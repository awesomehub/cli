<?php
namespace Hub\Exception;

/**
 * Exception handler manager registerer trit.
 *
 * @package AwesomeHub
 */
trait ExceptionHandlerManagerRegistererTrait
{
    /**
     * @var ExceptionHandlerManagerInterface
     */
    private static $instance;

    /**
     * @inheritdoc
     */
    public function register()
    {
        set_exception_handler([$this, 'runHandlers']);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function unregister()
    {
        restore_exception_handler();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
