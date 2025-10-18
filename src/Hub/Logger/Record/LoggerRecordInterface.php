<?php

declare(strict_types=1);

namespace Hub\Logger\Record;

/**
 * Interface for a LoggerRecord.
 */
interface LoggerRecordInterface
{
    /**
     * Gets the log level.
     */
    public function getLevel(): string;

    /**
     * Checks whether the record is of a certain level.
     */
    public function isLevel(string $level): bool;

    /**
     * Gets the log message.
     */
    public function getMessage(): string;

    /**
     * Gets a specific value from the log context or the whole
     *  array if the key is omitted.
     */
    public function getContext(?string $key = null): mixed;

    /**
     * Gets the log UNIX timestamp.
     */
    public function getTimestamp(): int;
}
