<?php
namespace Hub\Logger\Handler;

use Hub\Logger\Record\LoggerRecordInterface;

/**
 * Logger handler interface class.
 *
 * @package AwesomeHub
 */
interface LoggerHandlerInterface
{
    /**
     * Handles the log record.
     *
     * @param LoggerRecordInterface $record
     * @return void
     */
    public function handle($record);

    /**
     * Determines if the handler is going to handle this log level.
     *
     * @param LoggerRecordInterface $record
     * @return bool
     */
    public function isHandling($record);
}
