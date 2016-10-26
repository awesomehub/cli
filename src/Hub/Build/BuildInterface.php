<?php

namespace Hub\Build;

/**
 * Interface for a Build.
 */
interface BuildInterface
{
    /**
     * Gets a build path.
     *
     * @param array|string $path Path segments as array or string
     * @param bool         $raw Ignores file format extension
     *
     * @return string
     */
    public function getPath($path = null, $raw = false);

    /**
     * Gets the build number.
     *
     * @return string
     */
    public function getNumber();

    /**
     * Gets the build date.
     *
     * @return string
     */
    public function getDate();

    /**
     * Gets the build format.
     *
     * @return string
     */
    public function getFormat();

    /**
     * Gets a meta paramater.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key = null);

    /**
     * Sets the value of a meta paramater or the whole meta array.
     *
     * @param string|array $key
     * @param mixed|null   $value
     */
    public function set($key, $value = null);

    /**
     * Writes a file into the build directory.
     *
     * @param string $path
     * @param string $data
     * @param bool   $raw  Ignores file encoding
     *
     * @return bool
     */
    public function write($path, $data, $raw = false);

    /**
     * Reads a file from the build directory.
     *
     * @param string $path
     * @param bool   $raw  Ignores file decoding
     *
     * @return string
     */
    public function read($path, $raw = false);

    /**
     * Checks if a file from the build directory does exist.
     *
     * @param string $path
     * @param bool   $raw Ignores file format extension
     *
     * @return bool
     */
    public function exists($path = null, $raw = false);

    /**
     * Cleans the build directory.
     */
    public function clean();
}
