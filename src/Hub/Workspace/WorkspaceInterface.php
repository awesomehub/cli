<?php
namespace Hub\Workspace;

/**
 * Interface for a Workspace.
 *
 * @package AwesomeHub
 */
interface WorkspaceInterface
{
    /**
     * Gets a workspace path.
     *
     * @param $path array|string Path segments as array
     * @return string
     */
    public function path($path = []);
}
