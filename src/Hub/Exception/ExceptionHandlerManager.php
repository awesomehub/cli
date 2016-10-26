<?php

namespace Hub\Exception;

use Hub\Exception\Handler\ExceptionHandlerInterface;

/**
 * Exception handler manager class.
 */
class ExceptionHandlerManager implements ExceptionHandlerManagerInterface
{
    use ExceptionHandlerManagerRegistererTrait;

    /**
     * @var ExceptionHandlerInterface[]
     */
    private $handlers;

    /**
     * Logger constructor.
     *
     * @param ExceptionHandlerInterface[] $handlers
     */
    public function __construct(array $handlers = [])
    {
        $this->setHandlers($handlers);
    }

    /**
     * {@inheritdoc}
     */
    public function addHandler(ExceptionHandlerInterface $handler): self
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
    public function runHandlers(\Exception $exception)
    {
        if (0 === count($this->handlers)) {
            throw new \LogicException('No exception handler has been defined.');
        }

        foreach ($this->handlers as $handler) {
            if (!$handler->isHandling($exception)) {
                continue;
            }

            $handler->handle($exception);
        }

        $exitCode = $exception->getCode();
        if (is_numeric($exitCode)) {
            $exitCode = (int) $exitCode;
            if (0 === $exitCode) {
                $exitCode = 1;
            }
        } else {
            $exitCode = 1;
        }

        exit($exitCode);
    }
}
