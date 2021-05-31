<?php

namespace Hub\Logger\Record;

/**
 * Represents a logger record.
 */
class LoggerRecord implements LoggerRecordInterface
{
    public function __construct(protected string $level, protected string $message, protected int $timestamp, protected array $context = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * {@inheritdoc}
     */
    public function isLevel(string $level): bool
    {
        return $level === $this->level;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(string $key = null): mixed
    {
        if ($key) {
            return $this->context[$key] ?? null;
        }

        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
}
