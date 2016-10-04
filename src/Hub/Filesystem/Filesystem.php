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
     * @return string
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function read($path)
    {
        if (!is_file($path)) {
            throw new FileNotFoundException(sprintf('Failed to read "%s" because file does not exist.', $path), 0, null, $path);
        }

        $data = file_get_contents($path);
        if(false === $data){
            throw new IOException(sprintf('Failed to read data from file "%s".', $path), 0, null, $path);
        }

        return $data;
    }

    /**
     * Write the contents of a file.
     *
     * @param string $path
     * @param string $contents
     * @param bool $lock
     * @return int
     * @throws IOException
     */
    public function write($path, $contents, $lock = true)
    {
        $dir = dirname($path);
        if(!is_dir($dir)){
            $this->mkdir($dir);
        }

        $bytes = file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
        if(false === $bytes){
            throw new IOException(sprintf('Failed to write data to file "%s".', $path), 0, null, $path);
        }

        return $bytes;
    }

    /**
     * Appends data to a file.
     *
     * @param string $path
     * @param string $data
     * @param bool $lock
     * @return int
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function append($path, $data, $lock = true)
    {
        if (!is_file($path)) {
            throw new FileNotFoundException(sprintf('Failed to append data to "%s" because file does not exist.', $path), 0, null, $path);
        }

        $bytes = file_put_contents($path, $data, FILE_APPEND | ($lock ? LOCK_EX : 0));
        if(false === $bytes){
            throw new IOException(sprintf('Failed to append data to file "%s".', $path), 0, null, $path);
        }

        return $bytes;
    }
}
