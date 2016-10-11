<?php
namespace Hub\Logger;

use Psr\Log\LoggerInterface;
use Hub\Logger\Handler\LoggerHandlerInterface;
use Hub\Logger\Record\LoggerRecordInterface;

/**
 * Interface for a logger manager.
 *
 * @package AwesomeHub
 */
interface LoggerManagerInterface extends LoggerInterface
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
     * @return self
     */
    public function setHandlers(array $handlers);

    /**
     * @return LoggerHandlerInterface[]
     */
    public function getHandlers();

    /**
     * @param LoggerRecordInterface $record
     */
    public function runHandlers(LoggerRecordInterface $record);
}
