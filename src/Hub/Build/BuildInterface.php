<?php

declare(strict_types=1);

namespace Hub\Build;

/**
 * Interface for a Build.
 */
interface BuildInterface
{
    /**
     * Gets a build path.
     *
     * @param null|array|string $path Path segments as array or string
     * @param bool              $raw  Ignores file format extension
     */
    public function getPath(array|string|null $path = null, bool $raw = false): string;

    /**
     * Gets the build number.
     */
    public function getNumber(): string;

    /**
     * Gets the build date.
     */
    public function getDate(): string;

    /**
     * Gets the build format.
     */
    public function getFormat(): string;

    /**
     * Gets a meta parameter.
     */
    public function get(?string $key = null): mixed;

    /**
     * Sets the value of a meta parameter or the whole meta array.
     */
    public function set(array|string $key, mixed $value = null): void;

    /**
     * Writes a file into the build directory.
     *
     * @param bool $raw Ignore data serialization
     */
    public function write(string $path, mixed $data, bool $raw = false): string;

    /**
     * Reads a file from the build directory.
     *
     * @param string $path File path
     * @param bool   $raw  Prefer raw data
     */
    public function read(string $path, bool $raw = false): array|string;

    /**
     * Checks if a file from the build directory does exist.
     *
     * @param bool $raw Ignores file format extension
     */
    public function exists(?string $path = null, bool $raw = false): bool;

    /**
     * Finalizes the build at the end of the build process.
     */
    public function finalize(): void;

    /**
     * Cleans the build directory.
     */
    public function clean(): void;
}
