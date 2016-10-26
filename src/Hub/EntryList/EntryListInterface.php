<?php

namespace Hub\EntryList;

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
}
