<?php

namespace Hub\Logger\Record;

/**
 * Interface for a LoggerRecord.
 */
interface LoggerRecordInterface
{
    /**
     * Gets the log level.
     *
     * @return string
     */
    public function getLevel(): string;

    /**
     * Checks whether the record is of a certain level.
     *
     * @param string $level
     *
     * @return bool
     */
    public function isLevel($level): bool;

    /**
     * Gets the log message.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Gets a specific value from the log context or the whole
     *  array if the key is ommited.
     *
     * @param string $key
     *
     * @return string|array
     */
    public function getContext($key = null);

    /**
     * Gets the log UNIX timestamp.
     *
     * @return int
     */
    public function getTimestamp(): int;
}
