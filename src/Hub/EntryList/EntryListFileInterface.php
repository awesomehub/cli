<?php
namespace Hub\EntryList;

/**
 * Interface for an EntryListFile.
 *
 * @package AwesomeHub
 */
interface EntryListFileInterface extends EntryListInterface
{
    /**
     * Gets the list file path.
     *
     * @return string
     */
    function getPath();

    /**
     * Gets the list file format.
     *
     * @return string
     */
    function getFormat();
}
