<?php

namespace Hub\EntryList;

use Hub\Entry\EntryInterface;
use Hub\Entry\Resolver\EntryResolverInterface;
use Hub\EntryList\SourceProcessor\SourceProcessorInterface;
use Hub\IO\IOInterface;

/**
 * Interface for an EntryList.
 */
interface EntryListInterface
{
    /**
     * Processes the list file and creates list entries.
     *
     * @param SourceProcessorInterface[] $processors
     */
    public function process(IOInterface $io, array $processors);

    /**
     * Resolves the entries within the list.
     *
     * @param EntryResolverInterface[] $resolvers
     * @param bool                     $force
     */
    public function resolve(IOInterface $io, array $resolvers, $force = false);

    /**
     * The last perocess of list building.
     */
    public function finalize(IOInterface $io);

    /**
     * Removes an entry from the list and recounts category stats.
     */
    public function removeEntry(EntryInterface $entry);

    /**
     * Checks whether the list has been processed.
     *
     * @return bool
     */
    public function isProcessed();

    /**
     * Checks whether the list has been resolved.
     *
     * @return bool
     */
    public function isResolved();

    /**
     * Gets the list ID.
     *
     * @return string
     */
    public function getId();

    /**
     * Gets the list categories.
     *
     * @return array
     */
    public function getCategories();

    /**
     * Gets the list ientries.
     *
     * @return EntryInterface[]
     */
    public function getEntries();

    /**
     * Gets the value of a given data key. If the key is omitted, the whole data will be returned.
     *
     * @param string $key
     *
     * @return array
     */
    public function get($key = null);

    /**
     * Sets the value of a given data key or the whole data array.
     *
     * @param array|string $key
     * @param mixed        $value
     */
    public function set($key, $value = null);

    /**
     * Checks if a given key exists in list data.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key);
}
