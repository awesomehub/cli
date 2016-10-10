<?php
namespace Hub\Environment;

/**
 * Interface for an Environment.
 *
 * @package AwesomeHub
 */
interface EnvironmentInterface
{
    const DEVELOPMENT   = 'dev';
    const PRODUCTION    = 'prod';

    /**
     * Gets the value of an environment variable.
     *
     * @param string $varname
     * @return string
     */
    public function get($varname);

    /**
     * Gets the user home directory path.
     *
     * @return string|bool
     */
    public function getUserHome();

    /**
     * Gets the current script path.
     *
     * @return string
     */
    public function getBin();

    /**
     * Gets the environment mode.
     *
     * @return string
     */
    public function getMode();

    /**
     * Checks if running in development mode.
     *
     * @return boolean
     */
    public function isDevelopment();

    /**
     * Checks if running in production mode.
     *
     * @return boolean
     */
    public function isProduction();

    /**
     * Checks if running in windows platform.
     *
     * @return boolean
     */
    public function isPlatformWindows();
}
