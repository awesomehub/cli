<?php

namespace Hub\Entry;

/**
 * Interface for an Entry.
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
     * Checks if a given key exists in entry data.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * Gets the value of a given data key. If the key is omitted, the whole data will be returned.
     *
     * @param string $key
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
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
     * Merges the value of a given data key and if an array is given
     *  it should merge it with the main data array.
     *
     * @param string|array $key
     * @param mixed        $value
     * @param bool         $preserveIntegerKeys
     */
    public function merge($key, $value = null, $preserveIntegerKeys = false);

    /**
     * Delets a given data key.
     *
     * @param string $key
     */
    public function unset($key);
}
