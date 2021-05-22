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
    public function __construct(string $author, string $name)
    {
        parent::__construct(self::getType().':'.$author.'/'.$name, [
            'author' => $author,
            'name' => $name,
        ]);
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
