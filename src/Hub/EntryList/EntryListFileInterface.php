<?php

namespace Hub\EntryList;

/**
 * Interface for an EntryListFile.
 */
interface EntryListFileInterface extends EntryListInterface
{
    /**
     * Gets the list file path.
     *
     * @return string
     */
    public function getPath();

    /**
     * Gets the list cache file path.
     *
     * @return string
     */
    public function getCachePath();

    /**
     * Gets the list file format.
     *
     * @return string
     */
    public function getFormat();
}
