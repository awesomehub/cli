<?php

namespace Hub\Filesystem;

use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class Filesystem extends BaseFilesystem
{
    /**
     * Reads the contents of a file.
     *
     * @param string $path
     *
     * @throws FileNotFoundException
     * @throws IOException
     *
     * @return string
     */
    public function read($path)
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
     * @param string $path
     * @param string $contents
     * @param bool   $lock
     *
     * @throws IOException
     *
     * @return int
     */
    public function write($path, $contents, $lock = true)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            $this->mkdir($dir);
        }

        $bytes = file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
        if (false === $bytes) {
            throw new IOException(sprintf('Failed to write data to file "%s".', $path), 0, null, $path);
        }

        return $bytes;
    }

    /**
     * Appends data to a file.
     *
     * @param string $path
     * @param string $data
     * @param bool   $lock
     *
     * @throws FileNotFoundException
     * @throws IOException
     *
     * @return int
     */
    public function append($path, $data, $lock = true)
    {
        if (!is_file($path)) {
            throw new FileNotFoundException(sprintf('Failed to append data to "%s" because file does not exist.', $path), 0, null, $path);
        }

        $bytes = file_put_contents($path, $data, FILE_APPEND | ($lock ? LOCK_EX : 0));
        if (false === $bytes) {
            throw new IOException(sprintf('Failed to append data to file "%s".', $path), 0, null, $path);
        }

        return $bytes;
    }

    /**
     * Checks whether the path has the given extension or not.
     *
     * @param string $path
     * @param string $ext
     *
     * @return bool
     */
    public function hasExtension($path, $ext)
    {
        return '.'.strtolower($ext) === strtolower(substr($path, -1 * strlen($ext) - 1));
    }

    /**
     * Normalize path.
     *
     * @param string $path
     *
     * @throws \LogicException
     *
     * @return string
     */
    public function normalizePath($path)
    {
        $segments = [];
        foreach (preg_split('/[\/\\\\]+/', $path) as $part) {
            if ($part === '.') {
                continue;
            }

            if ($part !== '..') {
                array_push($segments, $part);
                continue;
            }

            if (count($segments) > 0 && end($segments) != '') {
                array_pop($segments);
            } else {
                throw new \LogicException('Path is outside of the defined root, path: ['.$path.']');
            }
        }

        return implode(DIRECTORY_SEPARATOR, $segments);
    }
}
