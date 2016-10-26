<?php

namespace Hub\EntryList\SourceProcessor;

use Hub\Entry\EntryInterface;
use Hub\Exceptions\SourceProcessorFailedException;

/**
 * Interface for a SourceProcessor.
 */
interface SourceProcessorInterface
{
    const INPUT_MARKDOWN     = 'markdown';
    const INPUT_MARKDOWN_URL = 'markdown.url';
    const INPUT_URLS         = 'urls';
    const INPUT_ENTRIES      = 'entries';

    const SUPPORTS = [
        self::INPUT_MARKDOWN,
        self::INPUT_MARKDOWN_URL,
        self::INPUT_URLS,
        self::INPUT_ENTRIES,
    ];

    const EVENT_ENTRY_CREATE  = 1;
    const EVENT_ENTRY_SUCCESS = 2;
    const EVENT_ENTRY_FAILED  = 3;

    /**
     * Processes the source and outputs new entry(s).
     *
     * @param array    $source
     * @param \Closure $callback Should receive 3 atgs ($event, $entry, $message)
     *
     * @throws SourceProcessorFailedException
     *
     * @return EntryInterface[]|EntryInterface Returns new entries on success
     */
    public function process(array $source, \Closure $callback = null);

    /**
     * Determines whether the processor supports the given source.
     *
     * @param array $source
     *
     * @return bool
     */
    public function supports(array $source);
}
