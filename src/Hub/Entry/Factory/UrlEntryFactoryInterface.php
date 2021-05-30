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
     * @throws UrlEntryCreationFailedException
     *
     * @return EntryInterface[] Returns new entries on success or FALSE on failure
     */
    public function create(array | string $input): array;
}
