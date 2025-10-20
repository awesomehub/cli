<?php

declare(strict_types=1);

namespace Hub\EntryList;

use Hub\Filesystem\Filesystem;
use Hub\IO\IOInterface;
use Hub\Workspace\WorkspaceInterface;
use Symfony\Component\Serializer;

/**
 * Creates list instances from files of different formats.
 */
class EntryListFile extends EntryList
{
    public const LISTS_DIR = 'lists';
    public const LISTS_CACHE_DIR = 'cache/lists';

    /**
     * Constructor.
     *
     * @throws \RuntimeException
     */
    public function __construct(protected Filesystem $filesystem, protected WorkspaceInterface $workspace, string $path, string $format)
    {
        // Check it it's relative path
        if (!$this->filesystem->isAbsolutePath($path)) {
            $path = $this->workspace->path([self::LISTS_DIR, $path]);
        }

        // Add $format extension if not present
        if (!$this->filesystem->hasExtension($path, $format) && !file_exists($path)) {
            $path .= '.'.$format;
        }

        try {
            $encodedData = $filesystem->read($path);
            if (empty($encodedData)) {
                throw new \InvalidArgumentException(\sprintf("File contents shall not be empty at '%s'", $path));
            }
        } catch (\Exception $e) {
            throw new \RuntimeException(\sprintf("Unable to read list definition file '%s'; %s", $path, $e->getMessage()), $e->getCode(), $e);
        }

        try {
            $data = $this->decode($encodedData, $format);
        } catch (\Exception $e) {
            throw new \RuntimeException(\sprintf("Unable to encode list definition file '%s'; %s", $path, $e->getMessage()), $e->getCode(), $e);
        }

        parent::__construct($data);
    }

    public function process(IOInterface $io, array $processors): void
    {
        parent::process($io, $processors);

        // Save the processed list
        $io->getLogger()->info('Saving list cache file');
        $this->save();
    }

    public function resolve(IOInterface $io, array $resolvers, bool $force = false, ?int $concurrency = null): void
    {
        parent::resolve($io, $resolvers, $force, $concurrency);

        $io->getLogger()->info('Saving list cache file');
        $this->save();
    }

    public function finalize(IOInterface $io): void
    {
        parent::finalize($io);

        $io->getLogger()->info('Saving list cache file');
        $this->save();
    }

    /**
     * Restores cached list instance from path.
     */
    public static function createFromCache(Filesystem $filesystem, WorkspaceInterface $workspace, string $id): self
    {
        $cachedPath = $workspace->path([self::LISTS_CACHE_DIR, $id]);
        if (!$filesystem->exists($cachedPath)) {
            throw new \InvalidArgumentException(\sprintf("Unable to find the list cache file at '%s'", $cachedPath));
        }

        $instance = unserialize($filesystem->read($cachedPath));
        if (!$instance instanceof self) {
            throw new \UnexpectedValueException('Malformed list cache file');
        }

        return $instance;
    }

    /**
     * Find list definition files.
     */
    public static function findLists(WorkspaceInterface $workspace): array
    {
        $lists = [];
        foreach (scandir($workspace->path(self::LISTS_DIR)) as $file) {
            if (is_dir($file) || !preg_match('/\.json$/i', $file)) {
                continue;
            }

            $lists[] = $file;
        }

        return $lists;
    }

    /**
     * Find cached list files.
     */
    public static function findCachedLists(WorkspaceInterface $workspace): array
    {
        $lists = [];
        foreach (scandir($workspace->path(self::LISTS_CACHE_DIR)) as $file) {
            if (\in_array($file, ['.', '..']) || is_dir($file)) {
                continue;
            }

            $lists[] = $file;
        }

        return $lists;
    }

    /**
     * Decodes given data into an array.
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \RuntimeException
     */
    protected function decode(string $data, string $format): array
    {
        $serializer = new Serializer\Encoder\ChainDecoder([
            new Serializer\Encoder\JsonDecode([
                Serializer\Encoder\JsonDecode::ASSOCIATIVE => true,
            ]),
        ]);

        if (!$serializer->supportsDecoding($format)) {
            throw new \LogicException(\sprintf("Unsupported list definition file format '%s'", $format));
        }

        return $serializer->decode($data, $format);
    }

    /**
     * Caches the list instance to file.
     */
    protected function save(): int
    {
        return $this->filesystem->write(
            $this->workspace->path([self::LISTS_CACHE_DIR, $this->getId()]),
            serialize($this)
        );
    }
}
