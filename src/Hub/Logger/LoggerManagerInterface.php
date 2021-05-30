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
     * Adds a handler to the stack.
     */
    public function addHandler(LoggerHandlerInterface $handler): self;

    /**
     * Replaces current handlers with the given handlers array.
     *
     * @param LoggerHandlerInterface[] $handlers
     */
    public function setHandlers(array $handlers): self;

    /**
     * Gets current active handlers.
     *
     * @return LoggerHandlerInterface[]
     */
    public function getHandlers(): array;

    /**
     * Runs current active handlers on the given log record.
     */
    public function runHandlers(LoggerRecordInterface $record): void;
}
