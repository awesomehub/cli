<?php

declare(strict_types=1);

namespace Hub\Entry;

use Hub\Util\NestedArray;

/**
 * Base class for providing common Entry functions.
 */
abstract class AbstractEntry implements EntryInterface
{
    protected array $data;
    protected array $aliases;

    /**
     * Constructor.
     *
     * @param string $id   Entry id
     * @param array  $data Initial entry data
     */
    public function __construct(private string $id, array $data = [])
    {
        $this->data = $data;
        $this->aliases = [];
    }

    public function addAlias(string $id): void
    {
        if (!\in_array($id, $this->aliases, true)) {
            $this->aliases[] = $id;
        }
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->data);
    }

    public function get(?string $key = null): mixed
    {
        if (0 === \func_num_args()) {
            return $this->data;
        }

        if (!\array_key_exists($key, $this->data)) {
            throw new \InvalidArgumentException(\sprintf("Trying to get an undefined entry data key '%s'", $key));
        }

        return $this->data[$key];
    }

    public function set(array|string $key, mixed $value = null): void
    {
        if (1 === \func_num_args()) {
            if (!\is_array($key)) {
                throw new \UnexpectedValueException(\sprintf('Expected array but got %s', var_export($key, true)));
            }

            $this->data = $key;

            return;
        }

        $this->data[$key] = $value;
    }

    public function merge(array|string $key, mixed $value = null): void
    {
        if (!\is_array($key)) {
            $key = [$key => $value];
        }

        $this->data = NestedArray::mergeDeep($this->data, $key);
    }

    public function unset(string $key): void
    {
        if (!\array_key_exists($key, $this->data)) {
            throw new \InvalidArgumentException(\sprintf("Trying to unset an undefined entry data key '%s'", $key));
        }

        unset($this->data[$key]);
    }

    final public function getId(): string
    {
        return $this->id;
    }
}
