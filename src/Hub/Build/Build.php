<?php

namespace Hub\Build;

use Symfony\Component\Serializer;
use Hub\Filesystem\Filesystem;

/**
 * Represents a build.
 */
class Build implements BuildInterface
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $meta;

    /**
     * Constructor.
     *
     * @param Filesystem $filesystem
     * @param string     $path
     * @param string     $number
     */
    public function __construct(Filesystem $filesystem, $path, $number = null)
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('The build path can not be empty');
        }

        $this->filesystem = $filesystem;
        $this->path       = $path;

        if (!empty($number)) {
            $this->set([
                'number' => $number,
                'date'   => date('c'),
                'format' => $this->getFormat(),
            ]);

            return;
        }

        if ($this->exists('build')) {
            $this->meta = $this->read('build');
        }

        if (empty($this->meta['number'])) {
            throw new \InvalidArgumentException('The build number can not be empty');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPath($path = null, $raw = false)
    {
        if (null === $path) {
            return $this->path;
        }

        if (empty($path)) {
            throw new \InvalidArgumentException('Path cannot br empty');
        }

        if (is_array($path)) {
            $path = implode('/', $path);
        }

        if (!$raw && !$this->filesystem->hasExtension($path, $this->getFormat())) {
            $path .= '.'.$this->getFormat();
        }

        $path = explode('/', str_replace('\\', '/', $path));
        array_unshift($path, $this->path);

        return implode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function getNumber()
    {
        return $this->meta['number'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDate()
    {
        return $this->meta['date'];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value = null)
    {
        if (null === $value) {
            if (!is_array($key)) {
                new \InvalidArgumentException(sprintf('Expected array but got %s', var_export($value, true)));
            }
            $this->meta = $key;
        } else {
            $this->meta[$key] = $value;
        }

        $this->write('build', $this->meta);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key = null)
    {
        if (null === $key) {
            return $this->meta;
        }

        return isset($this->meta[$key])
                ? $this->meta[$key]
                : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormat()
    {
        return 'json';
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $data, $raw = false)
    {
        $path = $this->getPath($path, $raw);
        try {
            if (!$raw) {
                $encoder = new Serializer\Encoder\JsonEncode();
                $data    = $encoder->encode($data, $this->getFormat());
            }

            $this->filesystem->write($path, $data);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf("Failed writing '%s'; %s", $path, $e->getMessage()), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read($path, $raw = false)
    {
        $path = $this->getPath($path, $raw);
        try {
            $encoded = $this->filesystem->read($path);
            if ($raw) {
                return $encoded;
            }

            $decoder = new Serializer\Encoder\JsonDecode(true);

            return $decoder->decode($encoded, $this->getFormat());
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf("Failed reading '%s'; %s", $path, $e->getMessage()), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists($path = null, $raw = false)
    {
        return file_exists($this->getPath($path, $raw));
    }

    /**
     * {@inheritdoc}
     */
    public function finalize()
    {
        $this->write('build', $this->meta);
    }

    /**
     * {@inheritdoc}
     */
    public function clean()
    {
        $this->filesystem->remove($this->path);
        $this->filesystem->mkdir($this->path);
    }
}
