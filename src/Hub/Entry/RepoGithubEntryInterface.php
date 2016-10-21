<?php

namespace Hub\Entry;

/**
 * Interface for an RepoGithubEntry.
 */
interface RepoGithubEntryInterface extends EntryInterface
{
    /**
     * Gets the author of the repo.
     *
     * @return string
     */
    public function getAuthor();

    /**
     * Gets the name of the repo.
     *
     * @return string
     */
    public function getName();
}
