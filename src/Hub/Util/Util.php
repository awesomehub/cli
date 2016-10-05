<?php

namespace Hub\Util;

/**
 * General utilities.
 *
 * @package AwesomeHub
 */
class Util {

    /**
     * Renders a php template.
     *
     * @param string $path
     * @param array $vars
     * @return string
     * @throws \Exception
     */
    public static function renderTemplate($path, array $vars = [])
    {
        if(!file_exists($path)){
            throw new \Exception("Unable to read template file '$path'");
        }

        return (function($template) {
            ob_start();
            extract($template['vars']);
            include($template['path']);
            return ob_get_clean();
        })([
            'path' => $path,
            'vars' => $vars
        ]);
    }

    /**
     * Executes an external program.
     *
     * @param string $command
     * @param bool $silent
     * @param array $output An array to be filled with every line of output from the command
     * @return bool
     * @throws \Exception
     */
    public function exec($command, $silent = false, &$output = null)
    {
        $output = null;
        $return_var = null;

        // Excute the command
        exec($command, $output, $return_var);

        // Check if successfull
        if ($return_var !== 0 && !$silent) {
            throw new \Exception("Command execution failed: $command\n Stdout:" . implode("\n", $output));
        }

        return 0 === $return_var;
    }
}
