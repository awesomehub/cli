<?php

declare(strict_types=1);

namespace Hub\Entry\Factory\UrlProcessor;

use Hub\Entry\EntryInterface;

/**
 * Interface for a UrlProcessor.
 */
interface UrlProcessorInterface
{
    /**
     * Causes the factory to move on to the next processor.
     */
    public const ACTION_SKIP = 0;

    /**
     * Causes the factory to exclusively use this processor to process the url.
     */
    public const ACTION_PROCESSING = 1;

    /**
     * Causes the factory to process the url with this processor then pass the result to the next processor.
     */
    public const ACTION_PARTIAL_PROCESSING = 2;

    /**
     * Processes the url then outputs new entry(s).
     *
     * @return bool|EntryInterface|EntryInterface[]|string|string[] When success it returns new entries or a single entry, on partial processing it returns child
     *                                                              urls or a single child url, on failure it returns FALSE
     */
    public function process(string $url);

    /**
     * Determines whether the processor supports this url.
     */
    public function getAction(string $url);
}
