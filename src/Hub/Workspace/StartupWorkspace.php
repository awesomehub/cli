<?php

declare(strict_types=1);

namespace Hub\Workspace;

use Hub\Application;
use Hub\Environment\EnvironmentInterface;
use Hub\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Represents the default app workspace that is created on startup.
 */
class StartupWorkspace extends Workspace
{
    public function __construct(EnvironmentInterface $env, InputInterface $input, Filesystem $filesystem)
    {
        $path = $this->getInputPath($input, $filesystem) ?: $this->getEnvironmentPath($env);

        parent::__construct($path, $filesystem);
    }

    /**
     * Fetches user defined workspace.
     */
    protected function getInputPath(InputInterface $input, Filesystem $filesystem): ?string
    {
        if ($input->hasParameterOption('--workspace', true)) {
            $path = $input->getParameterOption('--workspace', null, true);
        } elseif ($input->hasParameterOption('-w', true)) {
            $path = $input->getParameterOption('-w', null, true);
        } else {
            return null;
        }

        if (!$filesystem->isAbsolutePath($path)) {
            $path = getcwd().\DIRECTORY_SEPARATOR.$path;
        }

        try {
            $path = $filesystem->normalizePath($path);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid environment workspace supplied; {$e->getMessage()}", $e->getCode(), $e);
        }

        return $path;
    }

    /**
     * Tries tpo auto-detect the environment workspace based on different factors.
     */
    protected function getEnvironmentPath(EnvironmentInterface $env): string
    {
        // Check if in development mode
        if ($env->isDevelopment()) {
            $devPath = \dirname($env->getBin(), 2).\DIRECTORY_SEPARATOR.'.'.strtolower(Application::SLUG);
            if (file_exists($devPath)) {
                return $devPath;
            }
        }

        $envWorkspaceVar = strtoupper(Application::SLUG).'_WORKSPACE';
        if ($envWorkspace = $env->get($envWorkspaceVar)) {
            return $envWorkspace;
        }

        $home = $env->getUserHome();
        if (empty($home)) {
            throw new \RuntimeException('The HOME, APPDATA or '.$envWorkspaceVar.' environment variable must be set for '.Application::NAME.' to run correctly.');
        }

        return $env->isPlatformWindows()
            ? $home.'\\'.ucfirst(Application::SLUG)
            : $home.'/'.'.'.strtolower(Application::SLUG);
    }
}
