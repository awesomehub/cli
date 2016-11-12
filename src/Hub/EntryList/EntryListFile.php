<?php

namespace Hub\EntryList;

use Hub\IO\IOInterface;
use Hub\Workspace\WorkspaceInterface;
use Symfony\Component\Serializer;
use Hub\Filesystem\Filesystem;

/**
 * Creates list instances from files of different formats.
 */
class EntryListFile extends EntryList
{
    const LISTS_DIR       = 'lists';
    const LISTS_CACHE_DIR = 'cache/lists';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var WorkspaceInterface
     */
    protected $workspace;

    /**
     * Constructor.
     *
     * @param Filesystem         $filesystem
     * @param WorkspaceInterface $workspace
     * @param $path
     * @param $format
     *
     * @throws \RuntimeException
     */
    public function __construct(Filesystem $filesystem, WorkspaceInterface $workspace, $path, $format)
    {
        $this->filesystem = $filesystem;
        $this->workspace  = $workspace;

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
                throw new \InvalidArgumentException(sprintf("File contents shall not be empty at '%s'", $path));
            }
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf("Unable to read list definition file '%s'; %s", $path, $e->getMessage()));
        }

        try {
            $data = $this->decode($encodedData, $format);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf("Unable to encode list definition file '%s'; %s", $path, $e->getMessage()));
        }

        parent::__construct($data);
    }

    /**
     * {@inheritdoc}
     */
    public function process(IOInterface $io, array $processors)
    {
        parent::process($io, $processors);

        // Save the processed list
        $io->getLogger()->info('Saving list cache file');
        $this->save();
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(IOInterface $io, array $resolvers, $force = false)
    {
        parent::resolve($io, $resolvers, $force);

        $io->getLogger()->info('Saving list cache file');
        $this->save();
    }

    /**
     * Decodes given data into an array.
     *
     * @param string $data
     * @param string $format
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \RuntimeException
     *
     * @return array
     */
    protected function decode($data, $format)
    {
        $serializer = new Serializer\Encoder\ChainDecoder([
            new Serializer\Encoder\JsonDecode(true),
        ]);

        if (!$serializer->supportsDecoding($format)) {
            throw new \LogicException(sprintf("Unsupported list definition file format '%s'", $format));
        }

        return $serializer->decode($data, $format);
    }

    /**
     * Caches the list instance to file.
     *
     * @return int
     */
    protected function save()
    {
        return $this->filesystem->write(
            $this->workspace->path([self::LISTS_CACHE_DIR, $this->getId()]),
            serialize($this)
        );
    }

    /**
     * Restores cached list instance from path.
     *
     * @param Filesystem         $filesystem
     * @param WorkspaceInterface $workspace
     * @param string             $id
     *
     * @return self
     */
    public static function createFromCache(Filesystem $filesystem, WorkspaceInterface $workspace, $id)
    {
        $cachedPath = $workspace->path([self::LISTS_CACHE_DIR, $id]);
        if (!$filesystem->exists($cachedPath)) {
            throw new \InvalidArgumentException(sprintf("Unable to find the list cache file at '%s'", $cachedPath));
        }

        $instance = unserialize($filesystem->read($cachedPath));
        if (!$instance instanceof self) {
            throw new \UnexpectedValueException('Malformed list cache file');
        }

        return $instance;
    }

    /**
     * Find list definition files.
     *
     * @param WorkspaceInterface $workspace
     *
     * @return array
     */
    public static function findLists(WorkspaceInterface $workspace)
    {
        $lists = [];
        foreach (scandir($workspace->path(self::LISTS_DIR)) as $file) {
            if (in_array($file, ['.', '..']) || is_dir($file)) {
                continue;
            }

            $lists[] = $file;
        }

        return $lists;
    }

    /**
     * Find cached list files.
     *
     * @param WorkspaceInterface $workspace
     *
     * @return array
     */
    public static function findCachedLists(WorkspaceInterface $workspace)
    {
        $lists = [];
        foreach (scandir($workspace->path(self::LISTS_CACHE_DIR)) as $file) {
            if (in_array($file, ['.', '..']) || is_dir($file)) {
                continue;
            }

            $lists[] = $file;
        }

        return $lists;
    }
}
