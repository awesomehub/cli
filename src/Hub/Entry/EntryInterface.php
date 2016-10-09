<?php
namespace Hub\Entry;

/**
 * Interface for an Entry.
 *
 * @package AwesomeHub
 */
interface EntryInterface
{
    /**
     * The type constant should be overriden by implementors
     */
    const TYPE = 'generic';

    /**
     * Sets the value of a given data key and if an array is given
     *  it should merge it with the data property.
     *
     * @param string|array $key
     * @param mixed $value
     */
    public function set($key, $value = null);

    /**
     * Gets the value of a given data key. If the key is omitted, the whole data will be returned.
     *
     * @param string $key
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function get($key = null);

    /**
     * Delets a given data key.
     *
     * @param string $key
     */
    public function unset($key);

    /**
     * Resolves the entry and return the result as true/fale.
     *
     * @return bool
     */
    public function resolve();

    /**
     * Determines whether the entry is resolved.
     *
     * @return bool
     */
    public function isResolved();
}
