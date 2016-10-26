<?php

namespace Hub\Entry\Factory\UrlProcessor;

use Hub\Entry\EntryInterface;

/**
 * Interface for a UrlProcessor.
 */
interface UrlProcessorInterface
{
    /**
     * Causes the factory to move on to the nexr processor.
     */
    const ACTION_SKIP = 0;

    /**
     * Causes the factory to exclusively use this processor to process the url.
     */
    const ACTION_PROCESSING = 1;

    /**
     * Causes the factory to proccess the url with this processor then pass the result to the next prccessor.
     */
    const ACTION_PARTIAL_PROCESSING = 2;

    /**
     * Processes the url then outputs new entry(s).
     *
     * @param string $url
     *
     * @return EntryInterface[]|EntryInterface|bool Returns new entries on success or FALSE on failure
     */
    public function process($url);

    /**
     * Determines whether the processor supports this url.
     *
     * @param string $url
     *
     * @return int
     */
    public function getAction($url);
}
