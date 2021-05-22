<?php

namespace Hub\Entry\Factory;

use Hub\Entry\EntryInterface;

/**
 * Interface for an TypeEntryFactory.
 */
interface TypeEntryFactoryInterface
{
    /**
     * Creates new entry(s) based on input.
     *
     * @param array|string $type Entry type or an array of entry definitions
     * @param array        $data Entry data
     *
     * @return EntryInterface|EntryInterface[]
     */
    public static function create($type, array $data = []);

    /**
     * Gets a list of supported input types or checks whether the given type is supported.
     *
     * @param string $input Input type to check against
     *
     * @return array|bool
     */
    public static function supports($input = null);
}
