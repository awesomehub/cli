<?php
namespace Hub\Environment;

use Symfony\Component\Console\Input\InputInterface;
use Hub\Environment\Workspace\WorkspaceInterface;
use Hub\Environment\Workspace\Workspace;
use Hub\Filesystem\FilesystemUtil;
use Hub\Application;

/**
 * Responsible for handling environmental aspects.
 *
 * @package AwesomeHub
 */
class Environment implements EnvironmentInterface
{
    /**
     * @var string
     */
    protected $bin;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var WorkspaceInterface
     */
    protected $workspace;

    /**
     * @inheritdoc
     */
    public function __construct(InputInterface $input, $mode = null)
    {
        $this->setBin();
        $this->setMode($mode);
        $this->setWorkspace($this->getWorkspaceInput($input));
    }

    /**
     * @inheritdoc
     */
    public function getBin()
    {
        return $this->bin;
    }

    /**
     * @inheritdoc
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @inheritdoc
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @inheritdoc
     */
    public function isDevelopment()
    {
        return $this->mode === self::DEVELOPMENT;
    }

    /**
     * @inheritdoc
     */
    public function isProduction()
    {
        return $this->mode === self::PRODUCTION;
    }

    /**
     * Sets the environment worspace instance.
     *
     * @param $workspace string
     */
    protected function setWorkspace($workspace = null)
    {
        // Get the real absolute path
        if($workspace){
            if(FilesystemUtil::isRelativePath($workspace)){
                $workspace = getcwd() . DIRECTORY_SEPARATOR . $workspace;
            }

            try {
                $workspace = FilesystemUtil::normalizePath($workspace);
            }
            catch (\Exception $e){
                throw new \InvalidArgumentException("Invalid environment workspace supplied; {$e->getMessage()}");
            }
        }

        $this->workspace = new Workspace($workspace ?: $this->detectWorkspacePath());
    }

    /**
     * Tries tpo auto-detect the environment worspace based on different factors.
     *
     * @param void
     * @return string
     */
    protected function detectWorkspacePath()
    {
        // Check if in development mode
        if($this->isDevelopment()){
            return dirname(dirname($this->getBin())) . DIRECTORY_SEPARATOR . 'workspace';
        }

        $envWorkspaceVar = strtoupper(Application::SLUG) . '_WORKSPACE';
        if ($envWorkspace = getenv($envWorkspaceVar)) {
            return $envWorkspace;
        }

        // Check if on Windows platform
        if(defined('PHP_WINDOWS_VERSION_BUILD')){
            $envAppData = getenv('APPDATA');
            if (!$envAppData) {
                throw new \RuntimeException('The APPDATA or ' . $envWorkspaceVar . ' environment variable must be set for ' . Application::NAME . ' to run correctly');
            }

            return rtrim(strtr($envAppData, '\\', '/'), '/') . '/' . ucfirst(strtolower(Application::SLUG));
        }

        // Defaults to the $HOME directory
        $envHome = getenv('HOME');
        if (!$envHome) {
            throw new \RuntimeException('The HOME or ' . $envWorkspaceVar . ' environment variable must be set for ' . Application::NAME . ' to run correctly');
        }

        return rtrim(strtr($envHome, '\\', '/'), '/'). '/.' . strtolower(Application::SLUG);
    }

    /**
     * Fetches the user defined workspace.
     *
     * @param $input InputInterface
     * @return string|null
     */
    protected function getWorkspaceInput(InputInterface $input)
    {
        if ($input->hasParameterOption('--workspace', true)) {
            return $input->getParameterOption('--workspace', null, true);
        }

        if ($input->hasParameterOption('-w', true)) {
            return $input->getParameterOption('-w', null, true);
        }

        return null;
    }

    /**
     * Sets the current script path.
     *
     * @return void
     */
    protected function setBin()
    {
        $this->bin = realpath($_SERVER['argv'][0]);
    }

    /**
     * Sets the environment mode, tries to autguess if null.
     *
     * @param string|null $mode
     * @return void
     */
    protected function setMode($mode = null)
    {
        if($mode){
            if(!in_array($mode, [self::DEVELOPMENT, self::PRODUCTION])){
                throw new \InvalidArgumentException("Invalid environment mode supplied '$mode'.");
            }

            $this->mode = $mode;
            return;
        }

        // Check if we are inside phat
        if ('phar:' === substr(__FILE__, 0, 5)) {
            $this->mode = self::PRODUCTION;
            return;
        }

        // Check if ENV variable is defined
        if($envMode = getenv('ENV')){
            if(in_array(strtolower($envMode), ['development', 'dev'])){
                $this->mode = self::DEVELOPMENT;
            }
            else {
                $this->mode = self::PRODUCTION;
            }
            return;
        }

        // Check if a git repo is present
        if(file_exists(dirname(dirname($this->getBin())) . DIRECTORY_SEPARATOR . '.git')){
            $this->mode = self::DEVELOPMENT;
            return;
        }

        // Fallback to production
        $this->mode = self::PRODUCTION;
    }
}
