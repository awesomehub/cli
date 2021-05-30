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
     *
     * @return EntryInterface|EntryInterface[]
     */
    public static function create(array | string $type, array $data = []): EntryInterface | array;

    /**
     * Gets a list of supported input types or checks whether the given type is supported.
     *
     * @param null|string $input Input type to check against
     *
     * @return array|bool|string
     */
    public static function supports(string $input = null): bool | string | array;
}
