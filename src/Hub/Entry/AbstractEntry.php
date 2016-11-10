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
     * Constructor.
     *
     * @param array $data Initial entry data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key = null)
    {
        if (!$key) {
            return $this->data;
        }

        if (!array_key_exists($key, $this->data)) {
            throw new \InvalidArgumentException(sprintf("Trying to get an undefined entry data key '%s'", $key));
        }

        return $this->data[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value = null)
    {
        if (func_num_args() === 1) {
            if (!is_array($key)) {
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
    public function merge($key, $value = null, $preserveIntegerKeys = false)
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        $this->data = NestedArray::merge($this->data, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function unset($key)
    {
        if (!array_key_exists($key, $this->data)) {
            throw new \InvalidArgumentException(sprintf("Trying to unset an undefined entry data key '%s'", $key));
        }

        unset($this->data[$key]);
    }
}
