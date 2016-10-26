<?php

namespace Hub\Workspace;

/**
 * Interface for a Workspace.
 */
interface WorkspaceInterface
{
    /**
     * Gets a workspace path.
     *
     * @param array|string $path Path segments as array or string
     *
     * @return string
     */
    public function path($path = null);

    /**
     * Gets the value of a config key or gets the whole config.
     *
     * @param string $key
     *
     * @return string
     */
    public function config($key = null);
}
