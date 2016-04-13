<?php
namespace Docklyn\Exception;

use Docklyn\Exception\Handler\ExceptionHandlerInterface;

/**
 * Exception handler manager class.
 *
 * @package Docklyn
 */
class ExceptionHandlerManager extends ExceptionHandlerManagerRegisterAware
{
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
     * @inheritDoc
     */
    public function addHandler(ExceptionHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setHandlers(array $handlers)
    {
        $this->handlers = array();
        foreach ($handlers as $handler) {
            $this->addHandler($handler);
        }
    }

    /**
     * @inheritDoc
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * @inheritDoc
     */
    public function runHandlers(\Exception $exception)
    {
        if (0 === count($this->handlers)) {
            throw new \LogicException('No exception handler has been defined.');
        }

        foreach ($this->handlers as $handler){
            /* @var ExceptionHandlerInterface $handler */
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
