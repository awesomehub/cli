<?php
namespace Hub\Entry;

/**
 * Base class for providing common Entry functions.
 *
 * @package AwesomeHub
 */
abstract class AbstractEntry implements EntryInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var bool
     */
    protected $resolved;

    /**
     * Constructor.
     *
     * @param array $data Initial entry data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->resolved = false;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value = null)
    {
        if(is_array($key)){
            $this->data = array_merge($this->data, $key);
            return;
        }

        $this->data[$key] = $value;
    }

    /**
     * @inheritdoc
     */
    public function get($key = null)
    {
        if(!$key){
            return $this->data;
        }

        if(!array_key_exists($key, $this->data)){
            throw new \InvalidArgumentException("Trying to get an undefined entry data key '$key'.");
        }

        return $this->data[$key];
    }

    /**
     * @inheritdoc
     */
    public function unset($key)
    {
        if(!array_key_exists($key, $this->data)){
            throw new \InvalidArgumentException("Trying to unset an undefined entry data key '$key'.");
        }

        unset($this->data[$key]);
    }

    /**
     * @inheritdoc
     */
    public function resolve(array $data)
    {
        $this->set($data);
        $this->resolved = true;
    }

    /**
     * @inheritdoc
     */
    public function isResolved()
    {
        return $this->resolved;
    }
}
