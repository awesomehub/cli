<?php

namespace Hub\Logger;

use Hub\Logger\Handler\LoggerHandlerInterface;
use Hub\Logger\Record\LoggerRecordInterface;
use Psr\Log\LoggerInterface;

/**
 * Interface for a logger manager.
 */
interface LoggerManagerInterface extends LoggerInterface
{
    /**
     * Adds a handler on to the stack.
     *
     * @return self
     */
    public function addHandler(LoggerHandlerInterface $handler);

    /**
     * Set handlers, replacing all existing ones.
     *
     * @param LoggerHandlerInterface[] $handlers
     *
     * @return self
     */
    public function setHandlers(array $handlers);

    /**
     * @return LoggerHandlerInterface[]
     */
    public function getHandlers();

    public function runHandlers(LoggerRecordInterface $record);
}
