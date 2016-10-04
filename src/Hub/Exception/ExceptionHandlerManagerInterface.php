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
}