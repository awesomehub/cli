<?php
namespace Hub\Exception;

use Hub\Exception\Handler\ExceptionHandlerInterface;

/**
 * Interface for an exception handler manager.
 *
 * @package AwesomeHub
 */
interface ExceptionHandlerManagerInterface
{
    /**
     * Adds a handler on to the stack.
     *
     * @param  ExceptionHandlerInterface $handler
     * @return self
     */
    public function addHandler(ExceptionHandlerInterface $handler);

    /**
     * Set handlers, replacing all existing ones.
     *
     * @param  ExceptionHandlerInterface[] $handlers
     * @return self
     */
    public function setHandlers(array $handlers);

    /**
     * @return ExceptionHandlerInterface[]
     */
    public function getHandlers();

    /**
     * @param \Exception $exception
     */
    public function runHandlers(\Exception $exception);

    /**
     * Registers the current exception handler manager as
     *  the default php exception handler.
     *
     * @return self
     */
    function register();

    /**
     * Unregisters the current exception handler manager and
     *  restores the previous php exception handler.
     *
     * @return self
     */
    function unregister();

    /**
     * Singleton helper method to allow having one manager instance
     *  along the app live cycle.
     *
     * @return self
     */
    static function getInstance();
}