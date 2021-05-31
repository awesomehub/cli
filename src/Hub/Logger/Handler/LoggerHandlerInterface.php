<?php

declare(strict_types=1);

namespace Hub\Logger\Handler;

use Hub\Logger\Record\LoggerRecordInterface;

/**
 * Logger handler interface class.
 */
interface LoggerHandlerInterface
{
    /**
     * Handles the log record.
     */
    public function handle(LoggerRecordInterface $record): void;

    /**
     * Determines if the handler is going to handle this log level.
     */
    public function isHandling(LoggerRecordInterface $record): bool;
}
