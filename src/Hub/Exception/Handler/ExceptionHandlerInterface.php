<?php

namespace Hub\Exception\Handler;

/**
 * Interface for an exception handler.
 */
interface ExceptionHandlerInterface
{
    /**
     * Handles the exception.
     */
    public function handle(\Exception $exception): void;

    /**
     * Determines if the handler is going to handle this exception.
     */
    public function isHandling(\Exception $exception): bool;
}
