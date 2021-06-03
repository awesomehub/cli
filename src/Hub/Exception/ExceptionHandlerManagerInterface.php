<?php

declare(strict_types=1);

namespace Hub\Exception;

use Hub\Exception\Handler\ExceptionHandlerInterface;

/**
 * Interface for an exception handler manager.
 */
interface ExceptionHandlerManagerInterface
{
    /**
     * Adds a handler to the stack.
     */
    public function addHandler(ExceptionHandlerInterface $handler): self;

    /**
     * Replaces current handlers with the given handlers array.
     *
     * @param ExceptionHandlerInterface[] $handlers
     */
    public function setHandlers(array $handlers): self;

    /**
     * Gets current active handlers.
     *
     * @return ExceptionHandlerInterface[]
     */
    public function getHandlers(): array;

    /**
     * Runs current active handlers on the given exception.
     */
    public function runHandlers(\Throwable $exception);

    /**
     * Registers the current exception handler manager as
     *  the default php exception handler.
     */
    public function register(): self;

    /**
     * Singleton helper method to allow having one manager instance
     *  along the app live cycle.
     */
    public static function getInstance(): self;
}
