<?php

namespace Hub\Entry;

/**
 * Represents a Github repository entry.
 */
class RepoGithubEntry extends AbstractEntry implements RepoGithubEntryInterface
{
    /**
     * Constructor.
     *
     * @param string $author The repository author
     * @param string $name   The repository name
     */
    public function __construct($author, $name)
    {
        parent::__construct([
            'author' => $author,
            'name' => $name,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getType().':'.$this->data['author'].'/'.$this->data['name'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'repo.github';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthor()
    {
        return $this->data['author'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->data['name'];
    }
}
