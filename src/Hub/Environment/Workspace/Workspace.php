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
    public function get($path = null)
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
        return $this->get(['config.yaml']);
    }

    /**
     * Verifies the workspace and its directories.
     *
     * @param void
     */
    protected function verify()
    {
        if(!file_exists($this->path)){
            if(!mkdir($this->path)){
                throw new \RuntimeException("Unable to create workspace directory at '$this->path'.");
            }
        }

        if(!is_dir($this->path)){
            throw new \InvalidArgumentException("Workspace directory '$this->path' is not valid.");
        }

        $dirs = [
            'lists'
        ];

        foreach ($dirs as $dir){
            $dirPath = $this->path . DIRECTORY_SEPARATOR . $dir;
            if(!file_exists($dirPath)){
                if(!mkdir($dirPath)){
                    throw new \RuntimeException("Unable to create workspace directory at '$dirPath'.");
                }
            }
        }
    }
}
