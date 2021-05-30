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
     */
    public function resolve(EntryInterface $entry, bool $force = false): void;

    /**
     * Checks if a supported entry is cached.
     */
    public function isCached(EntryInterface $entry): bool;

    /**
     * Checks whether the resolver supports the given entry.
     */
    public function supports(EntryInterface $entry): bool;
}
