<?php
namespace Hub\Logger;

use Psr\Log\AbstractLogger;
use Hub\Logger\Handler\LoggerHandlerInterface;

/**
 * Logger manager class.
 *
 * @package AwesomeHub
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
        $this->handlers = array();
        foreach ($handlers as $handler) {
            $this->addHandler($handler);
        }
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
    public function runHandlers(array $record)
    {
        if (0 === count($this->handlers)) {
            throw new \LogicException('No logger handler has been defined.');
        }

        foreach ($this->handlers as $handler){
            /* @var LoggerHandlerInterface $handler */
            if (!$handler->isHandling($record['level'])) {
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
        $record = [
            'level' => $level,
            'message' => $this->interpolate((string) $message, $context),
            'context' => $context,
            'timestamp' => time(),
        ];

        $this->runHandlers($record);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @author PHP Framework Interoperability Group
     *
     * @param string $message
     * @param array  $context
     * @return string
     */
    private function interpolate($message, array $context)
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace[sprintf('{%s}', $key)] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
