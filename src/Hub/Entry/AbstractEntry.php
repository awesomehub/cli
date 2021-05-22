<?php

namespace Hub\Entry;

use Hub\Util\NestedArray;

/**
 * Base class for providing common Entry functions.
 */
abstract class AbstractEntry implements EntryInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $aliases;

    /**
     * @var string
     */
    private $id;

    /**
     * Constructor.
     *
     * @param string $id   Entry id
     * @param array  $data Initial entry data
     */
    public function __construct(string $id, array $data = [])
    {
        $this->id = $id;
        $this->data = $data;
        $this->aliases = [];
    }

    /**
     * {@inheritdoc}
     */
    final public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias($id)
    {
        if (!\in_array($id, $this->aliases, true)) {
            $this->aliases[] = $id;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key = null)
    {
        if (!$key) {
            return $this->data;
        }

        if (!\array_key_exists($key, $this->data)) {
            throw new \InvalidArgumentException(sprintf("Trying to get an undefined entry data key '%s'", $key));
        }

        return $this->data[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value = null)
    {
        if (1 === \func_num_args()) {
            if (!\is_array($key)) {
                throw new \UnexpectedValueException(sprintf('Expected array but got %s', var_export($key, true)));
            }

            $this->data = $key;

            return;
        }

        $this->data[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function merge($key, $value = null)
    {
        if (!\is_array($key)) {
            $key = [$key => $value];
        }

        $this->data = NestedArray::merge($this->data, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function unset($key)
    {
        if (!\array_key_exists($key, $this->data)) {
            throw new \InvalidArgumentException(sprintf("Trying to unset an undefined entry data key '%s'", $key));
        }

        unset($this->data[$key]);
    }
}
