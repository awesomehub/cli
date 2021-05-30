<?php

namespace Hub\Entry;

/**
 * Interface for an RepoEntry.
 */
interface RepoEntryInterface extends EntryInterface
{
    /**
     * Gets the author of the repo.
     */
    public function getAuthor(): string;

    /**
     * Gets the name of the repo.
     */
    public function getName(): string;
}
