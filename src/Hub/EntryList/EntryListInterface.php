<?php
namespace Hub\EntryList;

use Psr\Log\LoggerInterface;
use Hub\EntryList\SourceProcessor\SourceProcessorInterface;
use Hub\Entry\Resolver\EntryResolverInterface;

/**
 * Interface for an EntryList.
 *
 * @package AwesomeHub
 */
interface EntryListInterface
{
    const SOURCE_MARKDOWN = 'markdown';
    const SOURCE_MARKDOWN_URL = 'markdown.url';
    const SOURCE_ENTRIES = 'entries';

    /**
     * Processes the list file and creates list entries.
     *
     * @param LoggerInterface $logger
     * @param SourceProcessorInterface[] $processors
     * @param bool $force
     * @return bool
     */
    public function process(LoggerInterface $logger, array $processors, $force = false);

    /**
     * Resolves the entries within the list.
     *
     * @param LoggerInterface $logger
     * @param EntryResolverInterface[] $resolvers
     * @param bool $force
     * @return bool
     */
    public function resolve(LoggerInterface $logger, array $resolvers, $force = false);

    /**
     * Returns whether the list is processed or not.
     *
     * @return bool
     */
    public function isProcessed();

    /**
     * Returns whether the list is resolved or not.
     *
     * @return bool
     */
    public function isResolved();

    /**
     * Gets the value of a given data key. If the key is omitted, the whole data will be returned.
     *
     * @param string $key
     * @return array
     */
    public function get($key = null);
}
