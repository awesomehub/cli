<?php
namespace Hub\Workspace;

use Hub\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Represents an Environment workspace.
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
        $this->filesystem = $filesystem;

        $this->verify();
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
                catch (IOException $e){
                    throw new \RuntimeException("Failed creating child workspace directory '$dirPath'.", 0, $e);
                }
            }
        }
    }
}
