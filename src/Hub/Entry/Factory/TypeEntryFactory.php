<?php

declare(strict_types=1);

namespace Hub\Entry\Factory;

use Hub\Entry\EntryInterface;
use Hub\Entry\RepoGithubEntry;
use Hub\Exceptions\EntryCreationFailedException;

/**
 * Creates new Entry instances based on entry type and data.
 */
class TypeEntryFactory implements TypeEntryFactoryInterface
{
    private static array $supports = [
        RepoGithubEntry::class,
    ];

    /**
     * {@inheritdoc}
     */
    public static function create(array | string $type, array $data = []): array | EntryInterface
    {
        if (empty($type)) {
            throw new \UnexpectedValueException('Expected non empty entry type');
        }

        if (\is_array($type)) {
            $instances = [];
            foreach ($type as $i => $entry) {
                if (empty($entry['type']) || !\is_string($entry['type'])
                    || (isset($entry['data']) && !\is_array($entry['data']))
                ) {
                    throw new \UnexpectedValueException(sprintf('Expected an array [type: string, data: array], got %s', var_export($entry, true)));
                }

                $args = [$entry['type']];
                if (isset($entry['data'])) {
                    $args[] = $entry['data'];
                }

                $instances[$i] = self::create(...$args);
            }

            return $instances;
        }

        $class = self::supports($type);
        if (RepoGithubEntry::class === $class) {
            if (!isset($data['author'], $data['name'])) {
                throw new EntryCreationFailedException(sprintf("Unable to satisfy all required parameters for type '%s'; Given a data array with keys [%s]", $type, implode(', ', array_keys($data))));
            }

            return new RepoGithubEntry($data['author'], $data['name']);
        }

        throw new EntryCreationFailedException(sprintf("Unsupported entry type '%s'", $type));
    }

    /**
     * {@inheritdoc}
     */
    public static function supports(string $input = null): bool | string | array
    {
        $types = self::getTypeMap();
        if (0 === \func_num_args()) {
            return $types;
        }

        return $types[$input] ?? false;
    }

    /**
     * Gets type class map for all supported types.
     */
    private static function getTypeMap(): array
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
