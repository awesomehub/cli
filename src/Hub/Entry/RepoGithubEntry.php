<?php

declare(strict_types=1);

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
        parent::__construct(self::getType().':'.strtolower($author).'/'.strtolower($name), [
            'author' => $author,
            'name' => $name,
        ]);
    }

    public function getAuthor(): string
    {
        return $this->data['author'];
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public static function getType(): string
    {
        return 'repo.github';
    }
}
