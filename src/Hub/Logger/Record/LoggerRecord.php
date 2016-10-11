<?php
namespace Hub\Logger\Record;

/**
 * Represents a logger record.
 *
 * @package AwesomeHub
 */
class LoggerRecord implements LoggerRecordInterface
{
    /**
     * @var string $level
     */
    protected $level;

    /**
     * @var string $message
     */
    protected $message;

    /**
     * @var array $context
     */
    protected $context;

    /**
     * @var integer $timstamp
     */
    protected $timstamp;

    /**
     * Constructor.
     *
     * @param string $level
     * @param string $message
     * @param integer $timstamp
     * @param array $context
     */
    public function __construct($level, $message, $timstamp, array $context = [])
    {
        $this->level    = $level;
        $this->message  = $message;
        $this->timstamp = $timstamp;
        $this->context  = $context;
    }

    /**
     * @inheritdoc
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * @inheritdoc
     */
    public function isLevel($level): bool
    {
        return $level === $this->level;
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @inheritdoc
     */
    public function getContext($key = null)
    {
        if($key){
            return $this->context[$key] ?? null;
        }

        return $this->context;
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp(): int
    {
        return $this->timstamp;
    }
}
