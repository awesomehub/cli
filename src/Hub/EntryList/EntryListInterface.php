<?php

declare(strict_types=1);

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
    public function process(IOInterface $io, array $processors): void;

    /**
     * Resolves the entries within the list.
     *
     * @param EntryResolverInterface[] $resolvers
     */
    public function resolve(IOInterface $io, array $resolvers, bool $force = false): void;

    /**
     * The last process of list building.
     */
    public function finalize(IOInterface $io): void;

    /**
     * Removes an entry from the list and recounts category stats.
     */
    public function removeEntry(EntryInterface $entry): void;

    /**
     * Checks whether the list has been processed.
     */
    public function isProcessed(): bool;

    /**
     * Checks whether the list has been resolved.
     */
    public function isResolved(): bool;

    /**
     * Gets the list ID.
     */
    public function getId(): string;

    /**
     * Gets the list categories.
     */
    public function getCategories(): array;

    /**
     * Gets the list entries.
     *
     * @return EntryInterface[]
     */
    public function getEntries(): array;

    /**
     * Gets the value of a given data key. If the key is omitted, the whole list data will be returned.
     */
    public function get(?string $key = null): mixed;

    /**
     * Sets the value of a given data key or the whole list data array.
     */
    public function set(array|string $key, mixed $value = null): void;

    /**
     * Checks if a given key exists in list data.
     */
    public function has(string $key): bool;
}
