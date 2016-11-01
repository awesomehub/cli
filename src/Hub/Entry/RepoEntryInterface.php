<?php

namespace Hub\Entry;

/**
 * Interface for an RepoEntry.
 */
interface RepoEntryInterface extends EntryInterface
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
