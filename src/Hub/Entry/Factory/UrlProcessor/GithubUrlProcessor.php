<?php
namespace Hub\Entry\Factory\UrlProcessor;

use Hub\Entry\GithubRepoEntry;

/**
 * Create new entries based on github urls.
 *
 * @package AwesomeHub
 */
class GithubUrlProcessor implements UrlProcessorInterface
{
    /**
     * @var array
     */
    protected $matches;

    /**
     * @inheritdoc
     */
    public function process($url)
    {
        return new GithubRepoEntry($this->matches[1], $this->matches[2]);
    }

    /**
     * @inheritdoc
     */
    public function getProcessingAction($url)
    {
        if(preg_match('/http(?:s)?:\/\/(?:www\.)?github\.com\/([\w-]+)\/([\w-]+)/i', $url, $this->matches)) {
            return self::ACTION_PROCESSING;
        }

        return self::ACTION_SKIP;
    }
}
