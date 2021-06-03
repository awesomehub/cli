<?php

declare(strict_types=1);

namespace Hub\Exception\Handler;

/**
 * Interface for an exception handler.
 */
interface ExceptionHandlerInterface
{
    /**
     * Handles the exception.
     */
    public function handle(\Throwable $exception): void;

    /**
     * Determines if the handler is going to handle this exception.
     */
    public function isHandling(\Throwable $exception): bool;
}
