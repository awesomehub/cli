<?php
namespace Hub\Environment\Workspace;

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
     * @inheritdoc
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->verify();
    }

    /**
     * @inheritdoc
     */
    public function path($path = null)
    {
        if(is_array($path)){
            $path = implode(DIRECTORY_SEPARATOR, $path);
        }

        return $this->path . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        return $this->path(['config.yaml']);
    }

    /**
     * Verifies the workspace and its directories.
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

        $dirs = [
            'lists',
            'cache',
            'cache' . DIRECTORY_SEPARATOR . 'lists'
        ];

        foreach ($dirs as $dir){
            $dirPath = $this->path . DIRECTORY_SEPARATOR . $dir;
            if(!file_exists($dirPath)){
                if(!mkdir($dirPath)){
                    throw new \RuntimeException("Failed creating child workspace directory '$dirPath'.");
                }
            }
        }
    }
}
