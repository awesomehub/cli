<?php

namespace Hub\Entry\Resolver;

use Hub\Entry\EntryInterface;

/**
 * Interface for an EntryResolver.
 */
interface EntryResolverInterface
{
    /**
     * Resolves the given entry and returns the resolved entry.
     *
     * @param EntryInterface $entry
     * @param bool           $force
     */
    public function resolve(EntryInterface $entry, $force = false);

    /**
     * Checks if a supported entry is cached.
     *
     * @param EntryInterface $entry
     *
     * @return bool
     */
    public function isCached(EntryInterface $entry);

    /**
     * Checks whether the resolver supports the given entry.
     *
     * @param EntryInterface $entry
     *
     * @return bool
     */
    public function supports(EntryInterface $entry);
}
