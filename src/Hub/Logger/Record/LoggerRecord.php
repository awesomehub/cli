<?php

namespace Hub\Logger\Record;

/**
 * Represents a logger record.
 */
class LoggerRecord implements LoggerRecordInterface
{
    /**
     * @var string
     */
    protected $level;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var array
     */
    protected $context;

    /**
     * @var int
     */
    protected $timstamp;

    /**
     * Constructor.
     *
     * @param string $level
     * @param string $message
     * @param int    $timstamp
     * @param array  $context
     */
    public function __construct($level, $message, $timstamp, array $context = [])
    {
        $this->level    = $level;
        $this->message  = $message;
        $this->timstamp = $timstamp;
        $this->context  = $context;
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
    public function isLevel($level): bool
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
    public function getContext($key = null)
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
        return $this->timstamp;
    }
}
