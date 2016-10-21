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
     * Gets the id of the entry.
     *
     * @return string
     */
    public function getId();

    /**
     * Gets the type of the entry.
     *
     * @return string
     */
    public static function getType();

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
     * Marks the entry as resolved and merges the resolved data.
     *
     * @param array $data
     */
    public function resolve(array $data);

    /**
     * Determines whether the entry is resolved.
     *
     * @return bool
     */
    public function isResolved();
}
