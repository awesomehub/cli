<?php

namespace Hub\Filesystem;

/**
 * Filesystem Utilities.
 *
 * @package AwesomeHub
 */
class FilesystemUtil {

    public static function isRelativePath($path){
        return !preg_match('/^(?:\/|\\\\|\w:\\\\|\w:\/).*$/', $path);
    }

    /**
     * Normalize path.
     *
     * @param string $path
     * @return string
     * @throws \LogicException
     */
    public static function normalizePath($path)
    {
        $segments = [];
        foreach(preg_split('/[\/\\\\]+/', $path) as $part) {
            if ($part === '.')
                continue;

            if ($part !== '..') {
                array_push($segments, $part);
                continue;
            }

            if (count($segments) > 0 && end($segments) != "") {
                array_pop($segments);
            }
            else {
                throw new \LogicException('Path is outside of the defined root, path: [' . $path . ']');
            }
        }

        return join(DIRECTORY_SEPARATOR, $segments);
    }
}
