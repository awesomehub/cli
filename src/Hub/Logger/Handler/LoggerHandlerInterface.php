<?php
namespace Hub\Logger\Handler;

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
     * @param array[string] $record
     * @return void
     */
    public function handle($record);

    /**
     * Determines if the handler is going to handle this log level.
     *
     * @param string $level
     * @return bool
     */
    public function isHandling($level);
}
