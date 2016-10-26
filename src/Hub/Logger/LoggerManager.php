<?php

namespace Hub\Logger;

use Psr\Log\AbstractLogger;
use Hub\Logger\Record\LoggerRecordInterface;
use Hub\Logger\Handler\LoggerHandlerInterface;
use Hub\Logger\Record\LoggerRecord;

/**
 * Logger manager class.
 */
class LoggerManager extends AbstractLogger implements LoggerManagerInterface
{
    /**
     * @var LoggerHandlerInterface[]
     */
    private $handlers;

    /**
     * Logger constructor.
     *
     * @param LoggerHandlerInterface[] $handlers
     */
    public function __construct(array $handlers = [])
    {
        $this->setHandlers($handlers);
    }

    /**
     * {@inheritdoc}
     */
    public function addHandler(LoggerHandlerInterface $handler)
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setHandlers(array $handlers)
    {
        $this->handlers = [];
        foreach ($handlers as $handler) {
            $this->addHandler($handler);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * {@inheritdoc}
     */
    public function runHandlers(LoggerRecordInterface $record)
    {
        if (0 === count($this->handlers)) {
            throw new \LogicException('No logger handler has been defined.');
        }

        foreach ($this->handlers as $handler) {
            if (!$handler->isHandling($record)) {
                continue;
            }

            $handler->handle($record);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $this->runHandlers(new LoggerRecord(
            $level,
            $this->interpolate((string) $message, $context),
            time(),
            $context
        ));
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @author PHP Framework Interoperability Group
     *
     * @param string $message
     * @param array  $context
     *
     * @return string
     */
    private function interpolate($message, array $context)
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace[sprintf('{%s}', $key)] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
