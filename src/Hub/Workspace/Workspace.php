<?php
namespace Hub\Workspace;

use Symfony\Component\Serializer;
use Symfony\Component\Config as SymfonyConfig;
use Hub\Filesystem\Filesystem;

/**
 * Represents an app workspace.
 *
 * @package AwesomeHub
 */
class Workspace implements WorkspaceInterface
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $filesystem;

    /**
     * @var array
     */
    protected $structure = [
        'lists',
        'cache/lists'
    ];

    /**
     * Constructor.
     *
     * @param $path string Workspace path
     * @param $filesystem Filesystem
     */
    public function __construct($path, Filesystem $filesystem)
    {
        $this->path = rtrim($path, '/\\');
        $this->config = [];
        $this->filesystem = $filesystem;

        $this->verify();
        $this->setConfig();
    }

    /**
     * @inheritdoc
     */
    public function path($path = null)
    {
        if(null === $path){
            return $this->path;
        }

        if(!is_array($path)){
            $path = [$path];
        }

        array_unshift($path, $this->path);

        return implode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @inheritdoc
     */
    public function config($key = null)
    {
        if(null === $key){
            return $this->config();
        }

        if(array_key_exists($key, $this->config)){
            return $this->config[$key];
        }

        return $this->getConfigPath($key, $this->config);
    }

    /**
     * Verifies the workspace directory structure.
     *
     * @param void
     */
    protected function verify()
    {
        if(!file_exists($this->path)){
            $parent = dirname($this->path);
            if(!is_dir($parent) || !is_writable($parent)){
                throw new \RuntimeException("Failed creating workspace directory '$this->path'; Parent directory is not accessible or not writable.");
            }

            if(!mkdir($this->path)){
                throw new \RuntimeException("Failed creating workspace directory '$this->path'.");
            }
        }

        if(!is_dir($this->path)){
            throw new \InvalidArgumentException("Workspace directory '$this->path' is not valid.");
        }


        foreach ($this->structure as $dir){
            $dirPath = $this->path . DIRECTORY_SEPARATOR . $dir;
            if(!is_dir($dirPath)){
                try {
                    $this->filesystem->mkdir($dirPath);
                }
                catch (\Exception $e){
                    throw new \RuntimeException("Failed creating child workspace directory '$dirPath'.", 0, $e);
                }
            }
        }
    }

    /**
     * Loads and verifies workspace config file.
     *
     * @throws \RuntimeException
     */
    protected function setConfig()
    {
        $path = $this->path('config.json');
        if(!$this->filesystem->exists($path)){
            return;
        }

        try {
            $decoder = new Serializer\Encoder\JsonDecode(true);

            $encoded = $this->filesystem->read($path);
            $data = $decoder->decode($encoded, 'json');

            $processor = new SymfonyConfig\Definition\Processor();
            $this->config = $processor->processConfiguration(
                new Config\WorkspaceConfigDefinition(),
                [ $data ]
            );
        }
        catch (\Exception $e) {
            throw new \RuntimeException("Failed loading config.json file at '{$path}'; {$e->getMessage()}.", 0, $e);
        }
    }

    /**
     * Gets the value of a config path separated by dot.
     *
     * @param $path
     * @param array $config
     * @return bool|mixed
     */
    protected function getConfigPath($path, array $config)
    {
        $split = explode(".", $path, 2);
        if(!array_key_exists($split[0], $config)){
            return false;
        }

        if(!is_array($config[$split[0]])){
            return $config[$split[0]];
        }

        return $this->getConfigPath($split[1], $config[$split[0]]);
    }
}
