<?php

declare(strict_types=1);

namespace Hub\Entry;

/**
 * Interface for an Entry.
 */
interface EntryInterface
{
    /**
     * Gets the id of the entry.
     */
    public function getId(): string;

    /**
     * Gets the type of the entry.
     */
    public static function getType(): string;

    /**
     * Marks another entry id as an alias to thid entry.
     */
    public function addAlias(string $id);

    /**
     * Gets a list of other entry ids that are marked as aliases to this entry.
     */
    public function getAliases(): array;

    /**
     * Checks if a given key exists in entry data.
     */
    public function has(string $key): bool;

    /**
     * Gets the value of a given data key. If the key is omitted, the whole data will be returned.
     *
     * @throws \InvalidArgumentException
     */
    public function get(?string $key = null): mixed;

    /**
     * Sets the value of a given data key or the whole data array.
     */
    public function set(array|string $key, mixed $value = null);

    /**
     * Merges the value of a given data key. If an array is given
     *  it should merge it with the main data array.
     */
    public function merge(array|string $key, mixed $value = null);

    /**
     * Deletes a given data key.
     */
    public function unset(string $key);
}
