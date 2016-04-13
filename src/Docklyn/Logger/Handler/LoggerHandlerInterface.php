<?php
namespace Docklyn\Logger\Handler;

/**
 * Logger handler interface class.
 *
 * @package Docklyn
 */
interface LoggerHandlerInterface
{
    /**
     * Handles the log record.
     *
     * @param array $record
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
