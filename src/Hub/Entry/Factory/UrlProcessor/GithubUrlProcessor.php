<?php

namespace Hub\Entry\Factory\UrlProcessor;

use Hub\Entry\RepoGithubEntry;

/**
 * Create new entries based on github urls.
 */
class GithubUrlProcessor implements UrlProcessorInterface
{
    /**
     * @var array
     */
    protected $matches;

    /**
     * {@inheritdoc}
     */
    public function process($url)
    {
        return new RepoGithubEntry($this->matches[1], $this->matches[2]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAction($url)
    {
        if (preg_match('/http(?:s)?:\/\/+(?:www\.)?github\.com\/+([\w-]+)\/+([\w-]+)(?:\/?[\?\#].|$)/i', $url, $this->matches)) {
            return self::ACTION_PROCESSING;
        }

        return self::ACTION_SKIP;
    }
}
