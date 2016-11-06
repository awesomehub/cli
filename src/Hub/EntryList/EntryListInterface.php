<?php

namespace Hub\EntryList;

use Hub\Entry\EntryInterface;
use Hub\IO\IOInterface;
use Hub\EntryList\SourceProcessor\SourceProcessorInterface;
use Hub\Entry\Resolver\EntryResolverInterface;

/**
 * Interface for an EntryList.
 */
interface EntryListInterface
{
    /**
     * Gets the list ID.
     *
     * @return string
     */
    public function getId();

    /**
     * Processes the list file and creates list entries.
     *
     * @param IOInterface                $io
     * @param SourceProcessorInterface[] $processors
     */
    public function process(IOInterface $io, array $processors);

    /**
     * Resolves the entries within the list.
     *
     * @param IOInterface              $io
     * @param EntryResolverInterface[] $resolvers
     * @param bool                     $force
     */
    public function resolve(IOInterface $io, array $resolvers, $force = false);

    /**
     * Checks whether the list has been resolved.
     *
     * @return bool
     */
    public function isResolved();

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
     * @param string|array $key
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

    /**
     * Removes an entry from the list and recounts category stats.
     *
     * @param EntryInterface $entry
     */
    public function removeEntry(EntryInterface $entry);
}
