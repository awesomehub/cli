<?php

declare(strict_types=1);

namespace Hub\Filesystem;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;

class Filesystem extends BaseFilesystem
{
    /**
     * Reads the contents of a file.
     *
     * @throws IOException
     * @throws FileNotFoundException
     */
    public function read(string $path): string
    {
        if (!is_file($path)) {
            throw new FileNotFoundException(sprintf('Failed to read "%s" because file does not exist.', $path), 0, null, $path);
        }

        $data = file_get_contents($path);
        if (false === $data) {
            throw new IOException(sprintf('Failed to read data from file "%s".', $path), 0, null, $path);
        }

        return $data;
    }

    /**
     * Write the contents of a file.
     *
     * @throws IOException
     */
    public function write(string $path, string $contents, bool $lock = true): int
    {
        $dir = \dirname($path);
        if (!is_dir($dir)) {
            $this->mkdir($dir);
        }

        $bytes = file_put_contents($path, $contents, $lock ? \LOCK_EX : 0);
        if (false === $bytes) {
            throw new IOException(sprintf('Failed to write data to file "%s".', $path), 0, null, $path);
        }

        return $bytes;
    }

    /**
     * Appends data to a file.
     *
     * @throws IOException
     * @throws FileNotFoundException
     */
    public function append(string $path, string $data, bool $lock = true): int
    {
        if (!is_file($path)) {
            throw new FileNotFoundException(sprintf('Failed to append data to "%s" because file does not exist.', $path), 0, null, $path);
        }

        $bytes = file_put_contents($path, $data, \FILE_APPEND | ($lock ? \LOCK_EX : 0));
        if (false === $bytes) {
            throw new IOException(sprintf('Failed to append data to file "%s".', $path), 0, null, $path);
        }

        return $bytes;
    }

    /**
     * Checks whether the path has the given extension or not.
     */
    public function hasExtension(string $path, string $ext): bool
    {
        return '.'.strtolower($ext) === strtolower(substr($path, -1 * \strlen($ext) - 1));
    }

    /**
     * Normalize path.
     *
     * @throws \LogicException
     */
    public function normalizePath(string $path): string
    {
        $segments = [];
        foreach (preg_split('/[\/\\\\]+/', $path) as $part) {
            if ('.' === $part) {
                continue;
            }

            if ('..' !== $part) {
                $segments[] = $part;

                continue;
            }

            if ([] !== $segments && '' !== end($segments)) {
                array_pop($segments);
            } else {
                throw new \LogicException('Path is outside of the defined root, path: ['.$path.']');
            }
        }

        return implode(\DIRECTORY_SEPARATOR, $segments);
    }
}
