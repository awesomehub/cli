<?php

declare(strict_types=1);

namespace Hub\Workspace;

/**
 * Interface for a Workspace.
 */
interface WorkspaceInterface
{
    /**
     * Gets a workspace path.
     *
     * @param null|array|string $path Path segments as array or string
     */
    public function path(array | string $path = null): string;

    /**
     * Gets the value of a config key or gets the whole config array.
     */
    public function config(string $key = null, mixed $default = null): mixed;
}
