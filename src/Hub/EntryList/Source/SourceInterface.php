<?php

namespace Hub\EntryList\Source;

/**
 * Interface for a Source.
 */
interface SourceInterface
{
    /**
     * Gets the source type.
     *
     * @return string
     */
    public function getType();

    /**
     * Gets the source data.
     *
     * @return mixed
     */
    public function getData();

    /**
     * Gets all source options.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Gets an single option.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption($key, $default = null);

    /**
     * Checks whether a source has an option.
     *
     * @param string $key
     * @return bool
     */
    public function hasOption($key);
}
