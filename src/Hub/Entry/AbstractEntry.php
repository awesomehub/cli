<?php

namespace Hub\Entry;

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
    public function set($key, $value = null)
    {
        if ($value == null) {
            if (!is_array($key)) {
                throw new \UnexpectedValueException(sprintf('Expected array but got %s'), var_export($key));
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

        $this->data = $this->deepMerge($this->data, $key, $preserveIntegerKeys);
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
    public function unset($key)
    {
        if (!array_key_exists($key, $this->data)) {
            throw new \InvalidArgumentException(sprintf("Trying to unset an undefined entry data key '%s'", $key));
        }

        unset($this->data[$key]);
    }

    /**
     * Depp merge two arrays.
     *
     * @param array $a
     * @param array $b
     * @param bool  $preserveIntegerKeys
     *
     * @return array
     */
    protected function deepMerge(array $a, array $b, $preserveIntegerKeys = false)
    {
        $result = [];
        foreach ([$a, $b] as $array) {
            foreach ($array as $key => $value) {
                // Re-number integer keys as array_merge_recursive() does unless
                // $preserveIntegerKeys is set to true. Note that PHP automatically
                // converts array keys that are integer strings (e.g., '1') to integers.
                if (is_integer($key) && !$preserveIntegerKeys) {
                    $result[] = $value;
                } elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                    $result[$key] = $this->deepMerge($result[$key], $value, $preserveIntegerKeys);
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }
}
