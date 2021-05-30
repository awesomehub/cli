<?php

namespace Hub\Entry\Factory\UrlProcessor;

use Hub\Entry\RepoGithubEntry;
use Hub\Entry\RepoGithubEntryInterface;

/**
 * Creates new entries based on github urls.
 */
class GithubUrlProcessor implements UrlProcessorInterface
{
    protected array $matches;

    /**
     * {@inheritdoc}
     */
    public function process(string $url): RepoGithubEntryInterface
    {
        return new RepoGithubEntry($this->matches[1], $this->matches[2]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(string $url)
    {
        if (preg_match('/https?:\/\/+(?:www\.)?github\.com\/+([\w.-]+)\/+([\w.-]+)\/*(?:[?#].*|$)/i', $url, $this->matches)) {
            return self::ACTION_PROCESSING;
        }

        return self::ACTION_SKIP;
    }
}
