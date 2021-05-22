<?php

namespace Hub\Exceptions;

use Hub\Entry\Factory\UrlProcessor\UrlProcessorInterface;

/**
 * Represents an url processor exception.
 */
class UrlEntryCreationFailedException extends EntryCreationFailedException
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var UrlProcessorInterface
     */
    private $processor;

    /**
     * @param string     $message
     * @param string     $url
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($message, UrlProcessorInterface $processor, $url, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->processor = $processor;
        $this->url = $url;
    }

    /**
     * @return string The url failed to be processed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string The class name of the processor
     */
    public function getProcessor()
    {
        return \get_class($this->processor);
    }
}
