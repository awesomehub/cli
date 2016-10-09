<?php
namespace Hub\EntryList\SourceProcessor;

use Psr\Log\LoggerInterface;
use Hub\Entry\EntryInterface;

/**
 * Interface for a SourceProcessor.
 *
 * @package AwesomeHub
 */
interface SourceProcessorInterface
{
    const INPUT_MARKDOWN        = 'markdown';
    const INPUT_MARKDOWN_URL    = 'markdown.url';
    const INPUT_URLS            = 'urls';
    const INPUT_ENTRIES         = 'entries';

    const SUPPORTS = [
        self::INPUT_MARKDOWN,
        self::INPUT_MARKDOWN_URL,
        self::INPUT_URLS,
        self::INPUT_ENTRIES,
    ];

    /**
     * Processes the source and outputs new entry(s).
     *
     * @param LoggerInterface $logger
     * @param array $source
     * @return EntryInterface[]|EntryInterface|bool Returns new entries on success or FALSE on failure
     */
    public function process(LoggerInterface $logger, array $source);

    /**
     * Determines whether the processor supports the given source.
     *
     * @param array $source
     * @return bool
     */
    public function supports(array $source);
}
