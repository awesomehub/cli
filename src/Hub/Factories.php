<?php

declare(strict_types=1);

namespace Hub;

use Github\Utils\GithubTokenFactory;
use Github\Utils\GithubTokenPool;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Factory methods for creating specific services.
 */
class Factories
{
    /**
     * Creates a Filesystem cache adaptor.
     */
    public static function createFilesystemCache(string $path): FilesystemAdapter
    {
        return new FilesystemAdapter(null, 0, $path);
    }

    /**
     * Creates a GithubTokenPool instance.
     */
    public static function createGithubTokenPool(string $path, array $tokens): GithubTokenPool
    {
        return new GithubTokenPool($path, GithubTokenFactory::create($tokens));
    }
}
