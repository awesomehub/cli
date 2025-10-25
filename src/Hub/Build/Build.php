<?php

declare(strict_types=1);

namespace Hub\Build;

use Hub\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder;

/**
 * Represents a build.
 */
class Build implements BuildInterface
{
    protected string $path;
    protected string $format;
    protected Encoder\EncoderInterface $encoder;
    protected Encoder\DecoderInterface $decoder;
    protected array $meta;

    /**
     * Constructor.
     */
    public function __construct(protected Filesystem $filesystem, string $format, string $path, protected bool $hashed = true, ?string $number = null)
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('The build path can not be empty');
        }
        $this->path = $path;

        $format = strtolower($format);
        if (!\in_array($format, ['js', 'json'])) {
            throw new \InvalidArgumentException('Invalid build format provided');
        }
        $this->format = $format;
        $this->encoder = 'js' === $format ? new JsEncode() : new Encoder\JsonEncode();
        $this->decoder = 'js' === $format
            ? new JsDecode([Encoder\JsonDecode::ASSOCIATIVE => true])
            : new Encoder\JsonDecode([Encoder\JsonDecode::ASSOCIATIVE => true]);

        if (!empty($number)) {
            $this->set([
                'number' => $number,
                'date' => date('c'),
                'hashed' => $hashed,
                'urls' => new \stdClass(),
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
                throw new \InvalidArgumentException(\sprintf('Expected array but got %s', var_export($value, true)));
            }
            $this->meta = $key;
        } else {
            $this->meta[$key] = $value;
        }

        $this->write('build', $this->meta, hash: false);
    }

    public function get(?string $key = null): mixed
    {
        if (null === $key) {
            return $this->meta;
        }

        return $this->meta[$key] ?? false;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function write(string $path, mixed $data, bool $raw = false, bool $hash = true): string
    {
        try {
            if (!$raw) {
                $data = $this->encoder->encode($data, $this->getFormat());
            }

            $relPath = $path;
            if ($hash && $this->hashed) {
                $hash = $this->hashContent($data);
                $relPath = \sprintf('%s/%s.%s', $this->getNumber(), $path, $hash);
            }

            $path = $this->getPath($relPath, $raw);
            $this->filesystem->write($path, $data);

            return $relPath;
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

            return $this->decoder->decode($encoded, $this->getFormat());
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
        $this->write('build', $this->meta, hash: false);
    }

    public function clean(): void
    {
        if (!is_dir($this->path)) {
            throw new \RuntimeException(\sprintf("Build path '%s' does not exist. Expected a pre-configured directory (symlink) managed by the client app.", $this->path));
        }

        $paths = [];
        $iterator = new \FilesystemIterator($this->path, \FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $fileInfo) {
            $paths[] = $fileInfo->getPathname();
        }

        if (!empty($paths)) {
            $this->filesystem->remove($paths);
        }
    }

    protected function hashContent(string $content): string
    {
        if (\in_array('xxh64', hash_algos(), true)) {
            return hash('xxh64', $content);
        }

        return substr(hash('md5', $content), 0, 16);
    }
}
