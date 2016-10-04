<?php
namespace Hub\Exception\Handler;

/**
 * Interface for an exception handler.
 *
 * @package AwesomeHub
 */
interface ExceptionHandlerInterface
{
    /**
     * Handles the exception.
     *
     * @param \Exception $exception
     * @return void
     */
    public function handle(\Exception $exception);

    /**
     * Determines if the handler is going to handle this exception.
     *
     * @param \Exception $exception
     * @return bool
     */
    public function isHandling(\Exception $exception);
}
