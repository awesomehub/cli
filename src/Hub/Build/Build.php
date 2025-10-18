<?php

declare(strict_types=1);

namespace Hub\Build;

use Hub\Filesystem\Filesystem;
use Symfony\Component\Serializer;

/**
 * Represents a build.
 */
class Build implements BuildInterface
{
    protected string $path;
    protected array $meta;

    /**
     * Constructor.
     */
    public function __construct(protected Filesystem $filesystem, string $path, ?string $number = null)
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('The build path can not be empty');
        }
        $this->path = $path;

        if (!empty($number)) {
            $this->set([
                'number' => $number,
                'date' => date('c'),
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

    public function getPath(array|string|null $path = null, bool $raw = false): string
    {
        if (null === $path) {
            return $this->path;
        }

        if (empty($path)) {
            throw new \InvalidArgumentException('Path can not be empty');
        }

        if (\is_array($path)) {
            $path = implode('/', $path);
        }

        if (!$raw && !$this->filesystem->hasExtension($path, $this->getFormat())) {
            $path .= '.'.$this->getFormat();
        }

        $path = explode('/', str_replace('\\', '/', $path));
        array_unshift($path, $this->path);

        return implode(\DIRECTORY_SEPARATOR, $path);
    }

    public function getNumber(): string
    {
        return $this->meta['number'];
    }

    public function getDate(): string
    {
        return $this->meta['date'];
    }

    public function set(array|string $key, mixed $value = null): void
    {
        if (1 === \func_num_args()) {
            if (!\is_array($key)) {
                new \InvalidArgumentException(\sprintf('Expected array but got %s', var_export($value, true)));
            }
            $this->meta = $key;
        } else {
            $this->meta[$key] = $value;
        }

        $this->write('build', $this->meta);
    }

    public function get(?string $key = null): mixed
    {
        if (null === $key) {
            return $this->meta;
        }

        return $this->meta[$key]
                ?? false;
    }

    public function getFormat(): string
    {
        return 'json';
    }

    public function write(string $path, mixed $data, bool $raw = false): void
    {
        $path = $this->getPath($path, $raw);

        try {
            if (!$raw) {
                $encoder = new Serializer\Encoder\JsonEncode();
                $data = $encoder->encode($data, $this->getFormat());
            }

            $this->filesystem->write($path, $data);
        } catch (\Exception $e) {
            throw new \RuntimeException(\sprintf("Failed writing '%s'; %s", $path, $e->getMessage()), 0, $e);
        }
    }

    public function read(string $path, bool $raw = false): array|string
    {
        $path = $this->getPath($path, $raw);

        try {
            $encoded = $this->filesystem->read($path);
            if ($raw) {
                return $encoded;
            }

            $decoder = new Serializer\Encoder\JsonDecode([
                Serializer\Encoder\JsonDecode::ASSOCIATIVE => true,
            ]);

            return $decoder->decode($encoded, $this->getFormat());
        } catch (\Exception $e) {
            throw new \RuntimeException(\sprintf("Failed reading '%s'; %s", $path, $e->getMessage()), 0, $e);
        }
    }

    public function exists(?string $path = null, bool $raw = false): bool
    {
        return file_exists($this->getPath($path, $raw));
    }

    public function finalize(): void
    {
        $this->write('build', $this->meta);
    }

    public function clean(): void
    {
        $this->filesystem->remove($this->path);
        $this->filesystem->mkdir($this->path);
    }
}
