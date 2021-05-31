<?php

namespace Hub\Exceptions;

use Hub\Entry\Factory\UrlProcessor\UrlProcessorInterface;

/**
 * Represents an url processor exception.
 */
class UrlEntryCreationFailedException extends EntryCreationFailedException
{
    public function __construct(string $message, private UrlProcessorInterface $processor, private string $url, int $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string The url failed to be processed
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string The class name of the processor
     */
    public function getProcessor(): string
    {
        return $this->processor::class;
    }
}
