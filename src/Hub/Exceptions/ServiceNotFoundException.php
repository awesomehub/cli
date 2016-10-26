<?php

namespace Hub\Exceptions;

/**
 * Represents a not found container service.
 */
class ServiceNotFoundException extends \RuntimeException
{
    public function __construct($service, \Exception $previous = null)
    {
        parent::__construct("Service container cannot find a valid instance of '".ucfirst($service)."' service.", 0, $previous);
    }
}
