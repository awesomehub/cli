<?php

declare(strict_types=1);

namespace Hub\Logger\Record;

/**
 * Represents a logger record.
 */
class LoggerRecord implements LoggerRecordInterface
{
    public function __construct(protected string $level, protected string $message, protected int $timestamp, protected array $context = []) {}

    public function getLevel(): string
    {
        return $this->level;
    }

    public function isLevel(string $level): bool
    {
        return $level === $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContext(?string $key = null): mixed
    {
        if ($key) {
            return $this->context[$key] ?? null;
        }

        return $this->context;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
}
