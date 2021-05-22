<?php

namespace Hub\Exception;

use Hub\Exception\Handler\ExceptionHandlerInterface;

/**
 * Interface for an exception handler manager.
 */
interface ExceptionHandlerManagerInterface
{
    /**
     * Adds a handler on to the stack.
     *
     * @return self
     */
    public function addHandler(ExceptionHandlerInterface $handler);

    /**
     * Set handlers, replacing all existing ones.
     *
     * @param ExceptionHandlerInterface[] $handlers
     *
     * @return self
     */
    public function setHandlers(array $handlers);

    /**
     * @return ExceptionHandlerInterface[]
     */
    public function getHandlers();

    public function runHandlers(\Exception $exception);

    /**
     * Registers the current exception handler manager as
     *  the default php exception handler.
     *
     * @return self
     */
    public function register();

    /**
     * Singleton helper method to allow having one manager instance
     *  along the app live cycle.
     *
     * @return self
     */
    public static function getInstance();
}
