<?php
namespace Hub\Entry\Factory;

use Hub\Entry\EntryInterface;
use Hub\Exceptions\UrlEntryCreationFailedException;

/**
 * Interface for an EntryFactory.
 *
 * @package AwesomeHub
 */
interface EntryFactoryInterface
{
    /**
     * Creates new entry(s) based on input.
     *
     * @param mixed $input
     * @return EntryInterface[] Returns new entries on success or FALSE on failure
     * @throws UrlEntryCreationFailedException
     */
    public function create($input);
}
