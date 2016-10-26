<?php
namespace Hub\Build;

/**
 * Interface for a Build.
 *
 * @package AwesomeHub
 */
interface BuildInterface
{
    /**
     * Gets a build path.
     *
     * @param array|string $path Path segments as array or string
     * @param bool $raw
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
     * @return mixed
     */
    public function get($key = null);

    /**
     * Sets the value of a meta paramater or the whole meta array.
     *
     * @param string|array $key
     * @param mixed|null $value
     */
    public function set($key, $value = null);

    /**
     * Writes a file into the build directory.
     *
     * @param string $path
     * @param string $data
     * @param bool $raw Ignore encoding
     * @return bool
     */
    public function write($path, $data, $raw = false);

    /**
     * Reads a file from the build directory.
     *
     * @param string $path
     * @param bool $raw Ignore decoding
     * @return string
     */
    public function read($path, $raw = false);

    /**
     * Checks if a file from the build directory does exist.
     *
     * @param string $path
     * @param bool $raw
     * @return bool
     */
    public function exists($path = null, $raw = false);

    /**
     * Cleans the build directory.
     *
     * @return void
     */
    public function clean();
}
