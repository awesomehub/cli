<?php

namespace Hub;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Github\Utils\GithubTokenFactory;
use Github\Utils\GithubTokenPool;

/**
 * Factory methods for creating specific services.
 */
class Factories
{
    /**
     * Creates a Filesystem cache adaptor.
     *
     * @param string $path
     *
     * @return FilesystemAdapter
     */
    public static function createFilesystemCache($path)
    {
        return new FilesystemAdapter(null, 0, $path);
    }

    /**
     * Creates a GithubTokenPool instance.
     *
     * @param string $path
     * @param array  $tokens
     *
     * @return GithubTokenPool
     */
    public static function createGithubTokenPool($path, array $tokens)
    {
        return new GithubTokenPool($path, GithubTokenFactory::create($tokens));
    }
}
