<?php
namespace Hub\Entry;

/**
 * Represents a Github Repository.
 *
 * @package AwesomeHub
 */
class GithubRepoEntry extends AbstractEntry
{
    const TYPE = 'repo.github';

    /**
     * Constructor.
     *
     * @param string $author The repository author
     * @param string $name The repository name
     */
    public function __construct($author, $name)
    {
        parent::__construct([
            'author' => $author,
            'name' => $name
        ]);
    }

    /**
     * Gets the name of the repo.
     *
     * @return string
     */
    public function getName()
    {
        return $this->data['name'];
    }

    /**
     * Gets the author of the repo.
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->data['author'];
    }
}
