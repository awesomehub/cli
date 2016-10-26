<?php
namespace Hub\Entry\Resolver;

use Hub\Entry\EntryInterface;

/**
 * Interface for an EntryResolver.
 *
 * @package AwesomeHub
 */
interface EntryResolverInterface
{
    /**
     * Resolves the given entry and returns the resolved entry.
     *
     * @param EntryInterface $entry
     * @param bool $force
     */
    public function resolve(EntryInterface $entry, $force = false);

    /**
     * Checks whether a supported entry is resolved or not.
     *
     * @param EntryInterface $entry
     * @return bool
     */
    public function isResolved(EntryInterface $entry);

    /**
     * Checks whether the resolver supports the given entry.
     *
     * @param EntryInterface $entry
     * @return bool
     */
    public function supports(EntryInterface $entry);
}
