<?php
namespace Hub\Workspace;

use Symfony\Component\Console\Input\InputInterface;
use Hub\Environment\EnvironmentInterface;
use Hub\Filesystem\Filesystem;
use Hub\Application;

/**
 * Represents the default app workspace that is created on startup.
 *
 * @package AwesomeHub
 */
class StartupWorkspace extends Workspace
{
    /**
     * @inheritdoc
     */
    public function __construct(EnvironmentInterface $env, InputInterface $input, Filesystem $filesystem)
    {
        $path = $this->getInputPath($input, $filesystem) ?: $this->getEnvironmentPath($env);

        parent::__construct($path, $filesystem);
    }

    /**
     * Fetches user defined workspace.
     *
     * @param InputInterface $input
     * @param Filesystem $filesystem
     * @return string|null
     */
    protected function getInputPath(InputInterface $input, Filesystem $filesystem)
    {
        if ($input->hasParameterOption('--workspace', true)) {
            $path = $input->getParameterOption('--workspace', null, true);
        }
        else if ($input->hasParameterOption('-w', true)) {
            $path =  $input->getParameterOption('-w', null, true);
        }
        else {
            return null;
        }

        if(!$filesystem->isAbsolutePath($path)){
            $path = getcwd() . DIRECTORY_SEPARATOR . $path;
        }

        try {
            $path = $filesystem->normalizePath($path);
        }
        catch (\Exception $e){
            throw new \InvalidArgumentException("Invalid environment workspace supplied; {$e->getMessage()}");
        }

        return $path;
    }

    /**
     * Tries tpo auto-detect the environment worspace based on different factors.
     *
     * @param EnvironmentInterface $env
     * @return string
     */
    protected function getEnvironmentPath(EnvironmentInterface $env)
    {
        // Check if in development mode
        if($env->isDevelopment()){
            return dirname(dirname($env->getBin())) . DIRECTORY_SEPARATOR . '.' . strtolower(Application::SLUG);
        }

        $envWorkspaceVar = strtoupper(Application::SLUG) . '_WORKSPACE';
        if ($envWorkspace = $env->get($envWorkspaceVar)) {
            return $envWorkspace;
        }

        $home = $env->getUserHome();
        if(!$home){
            throw new \RuntimeException('The HOME, APPDATA or ' . $envWorkspaceVar . ' environment variable must be set for ' . Application::NAME . ' to run correctly.');
        }

        return $env->isPlatformWindows()
            ? $home . '\\' . ucfirst(Application::SLUG)
            : $home . '/' . '.' . strtolower(Application::SLUG);
    }
}
