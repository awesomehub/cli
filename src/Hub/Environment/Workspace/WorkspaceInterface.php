<?php
namespace Hub\Environment\Workspace;

/**
 * Interface for a Workspace.
 *
 * @package AwesomeHub
 */
interface WorkspaceInterface
{
    /**
     * Constructor.
     *
     * @param $path string Workspace path
     */
    public function __construct($path);

    /**
     * Gets a workspace path.
     *
     * @param $path array|string Path segments as array
     * @return string
     */
    public function get($path = []);

    /**
     * Gets the config file path.
     *
     * @param void
     * @return string
     */
    public function getConfig();
}
