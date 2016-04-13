<?php
namespace Docklyn\Logger;

use Docklyn\Logger\Handler\LoggerHandlerInterface;

/**
 * Interface for logger manager classes.
 *
 * @package Docklyn
 */
interface LoggerManagerInterface
{
    /**
     * Adds a handler on to the stack.
     *
     * @param  LoggerHandlerInterface $handler
     * @return self
     */
    public function addHandler(LoggerHandlerInterface $handler);

    /**
     * Set handlers, replacing all existing ones.
     *
     * @param  LoggerHandlerInterface[] $handlers
     */
    public function setHandlers(array $handlers);

    /**
     * @return LoggerHandlerInterface[]
     */
    public function getHandlers();

    /**
     * @param array $record
     */
    public function runHandlers(array $record);
}
