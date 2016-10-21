<?php

namespace Hub\Entry\Factory;

use Hub\Entry\EntryInterface;
use Hub\Exceptions\UrlEntryCreationFailedException;

/**
 * Interface for an UrlEntryFactory.
 */
interface UrlEntryFactoryInterface
{
    /**
     * Creates new entry(s) based on input url(s).
     *
     * @param array|string $input
     *
     * @return EntryInterface[] Returns new entries on success or FALSE on failure
     *
     * @throws UrlEntryCreationFailedException
     */
    public function create($input);
}