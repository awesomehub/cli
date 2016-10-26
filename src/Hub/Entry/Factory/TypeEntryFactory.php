<?php

namespace Hub\Entry\Factory;

use Hub\Entry\RepoGithubEntry;
use Hub\Exceptions\EntryCreationFailedException;

/**
 * Creates new Entry instances based on entry type and data.
 */
class TypeEntryFactory implements TypeEntryFactoryInterface
{
    private static $supports = [
        RepoGithubEntry::class,
    ];

    /**
     * {@inheritdoc}
     */
    public static function create($type, array $data = [])
    {
        if (empty($type)) {
            throw new \UnexpectedValueException('Expected non empty entry type');
        }

        if (is_array($type)) {
            $instances = [];
            foreach ($type as $i => $entry) {
                if (empty($entry['type']) || !is_string($entry['type'])
                    || (isset($entry['data']) && !is_array($entry['data']))
                ) {
                    throw new \UnexpectedValueException(sprintf(
                        'Expected an array [type: string, data: array], got %s',
                        var_export($entry, true)
                    ));
                }

                $args = [$entry['type']];
                if (isset($entry['data'])) {
                    $args[] = $entry['data'];
                }

                $instances[$i] = self::create(...$args);
            }

            return $instances;
        }

        switch (self::supports($type)) {
            case RepoGithubEntry::class:
                if (!isset($data['author']) || !isset($data['name'])) {
                    throw new EntryCreationFailedException(sprintf(
                        "Unable to satisfay all required paramaters for type '%s'; Given a data array with keys [%s]",
                        implode(', ', array_keys($data))
                    ));
                }

                return new RepoGithubEntry($data['author'], $data['name']);
        }

        throw new EntryCreationFailedException(sprintf("Unsupported entry type '%s'", $type));
    }

    /**
     * {@inheritdoc}
     */
    public static function supports($type = null)
    {
        $types = self::getTypeMap();
        if (null === $type) {
            return $types;
        }

        return $types[$type];
    }

    /**
     * Gets type class map for all supported types.
     *
     * @return array
     */
    private static function getTypeMap()
    {
        static $types;

        if (!$types) {
            $types = [];
            foreach (self::$supports as $class) {
                $types[$class::getType()] = $class;
            }
        }

        return $types;
    }
}
