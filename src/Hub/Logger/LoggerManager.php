<?php

namespace Hub\Logger;

use Hub\Logger\Handler\LoggerHandlerInterface;
use Hub\Logger\Record\LoggerRecord;
use Hub\Logger\Record\LoggerRecordInterface;
use Psr\Log\AbstractLogger;

/**
 * Logger manager class.
 */
class LoggerManager extends AbstractLogger implements LoggerManagerInterface
{
    /**
     * @var LoggerHandlerInterface[]
     */
    protected array $handlers;

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
    public function addHandler(LoggerHandlerInterface $handler): self
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setHandlers(array $handlers): self
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
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * {@inheritdoc}
     */
    public function runHandlers(LoggerRecordInterface $record): void
    {
        if ([] === $this->handlers) {
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
    public function log($level, $message, array $context = []): void
    {
        $this->runHandlers(new LoggerRecord(
            $level,
            $this->interpolate($message, $context),
            time(),
            $context
        ));
    }

    /**
     * Interpolates context values into the message placeholders.
     */
    protected function interpolate(string $message, array $context): string
    {
        // Build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            if (!\is_array($val) && (!\is_object($val) || method_exists($val, '__toString'))) {
                $replace[sprintf('{%s}', $key)] = $val;
            }
        }

        // Interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
