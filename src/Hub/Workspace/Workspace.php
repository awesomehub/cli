<?php

declare(strict_types=1);

namespace Hub\Workspace;

use Hub\Filesystem\Filesystem;
use Symfony\Component\Config as SymfonyConfig;
use Symfony\Component\Serializer;

/**
 * Represents an app workspace.
 */
class Workspace implements WorkspaceInterface
{
    protected string $path;
    protected array $config;
    protected array $structure = [
        'lists',
        'cache',
    ];

    public function __construct(string $path, protected Filesystem $filesystem)
    {
        $this->path = rtrim($path, '/\\');
        $this->config = [];

        $this->verify();
        $this->setConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function path(array | string $path = null): string
    {
        if (null === $path) {
            return $this->path;
        }

        if (\is_array($path)) {
            $path = implode('/', $path);
        }

        $path = explode('/', str_replace('\\', '/', $path));
        array_unshift($path, $this->path);

        return implode(\DIRECTORY_SEPARATOR, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function config(string $key = null, mixed $default = null): mixed
    {
        if (0 === \func_num_args()) {
            return $this->config;
        }

        if (\array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        try {
            return $this->getConfigPath($key);
        } catch (\InvalidArgumentException $e) {
            if (1 === \func_num_args()) {
                throw new \InvalidArgumentException("Can not find workspace config key '{$key}': {$e->getMessage()}", $e->getCode(), $e);
            }

            return $default;
        }
    }

    /**
     * Verifies the workspace directory structure.
     */
    protected function verify(): void
    {
        if (!file_exists($this->path)) {
            $parent = \dirname($this->path);
            if (!is_dir($parent) || !is_writable($parent)) {
                throw new \RuntimeException("Failed creating workspace directory '{$this->path}'; Parent directory is not accessible or not writable.");
            }

            try {
                $this->filesystem->mkdir($this->path);
            } catch (\Exception $e) {
                throw new \RuntimeException("Failed creating workspace directory '{$this->path}'.", 0, $e);
            }
        }

        if (!is_dir($this->path)) {
            throw new \InvalidArgumentException("Workspace directory '{$this->path}' is not valid.");
        }

        foreach ($this->structure as $dir) {
            $dirPath = $this->path.\DIRECTORY_SEPARATOR.$dir;
            if (!is_dir($dirPath)) {
                try {
                    $this->filesystem->mkdir($dirPath);
                } catch (\Exception $e) {
                    throw new \RuntimeException("Failed creating child workspace directory '{$dirPath}'.", 0, $e);
                }
            }
        }
    }

    /**
     * Loads and verifies workspace config file.
     *
     * @throws \RuntimeException
     */
    protected function setConfig(): void
    {
        $path = $this->path('config.json');
        if (!$this->filesystem->exists($path)) {
            return;
        }

        try {
            $encoded = $this->filesystem->read($path);

            $decoder = new Serializer\Encoder\JsonDecode([
                Serializer\Encoder\JsonDecode::ASSOCIATIVE => true,
            ]);

            $data = $decoder->decode($encoded, 'json');

            $processor = new SymfonyConfig\Definition\Processor();
            $this->config = $processor->processConfiguration(
                new Config\WorkspaceConfigDefinition(),
                [$data]
            );
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed loading config.json file at '{$path}'; {$e->getMessage()}.", 0, $e);
        }
    }

    /**
     * Gets the value of a config path separated by dot.
     *
     * @throws \InvalidArgumentException
     */
    protected function getConfigPath(string $path, array $config = null): mixed
    {
        if (1 === \func_num_args()) {
            $config = $this->config;
        }

        $split = explode('.', $path, 2);
        if (!\array_key_exists($split[0], $config)) {
            throw new \InvalidArgumentException("Invalid config path directive '{$split[0]}'");
        }

        if (!\is_array($config[$split[0]]) || !isset($split[1])) {
            return $config[$split[0]];
        }

        return $this->getConfigPath($split[1], $config[$split[0]]);
    }
}
