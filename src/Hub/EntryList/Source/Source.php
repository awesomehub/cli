<?php

namespace Hub\EntryList\Source;

/**
 * Represents an EntryList Source.
 */
class Source implements SourceInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var array
     */
    protected $options;

    public function __construct($type, $data, array $options = [])
    {
        $this->type    = $type;
        $this->data    = $data;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption($key)
    {
        return array_key_exists($key, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($key, $default = null)
    {
        if (!$this->hasOption($key)) {
            return $default;
        }

        return $this->options[$key];
    }
}
