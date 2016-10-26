<?php

namespace Hub\Logger\Handler;

use Hub\Logger\Record\LoggerRecordInterface;

/**
 * Logger handler interface class.
 */
interface LoggerHandlerInterface
{
    /**
     * Handles the log record.
     *
     * @param LoggerRecordInterface $record
     */
    public function handle($record);

    /**
     * Determines if the handler is going to handle this log level.
     *
     * @param LoggerRecordInterface $record
     *
     * @return bool
     */
    public function isHandling($record);
}
