<?php

namespace Hub\Environment;

/**
 * Interface for an Environment.
 */
interface EnvironmentInterface
{
    public const DEVELOPMENT = 'dev';
    public const PRODUCTION = 'prod';

    /**
     * Gets the value of an environment variable.
     *
     * @param string $varname
     *
     * @return string
     */
    public function get($varname);

    /**
     * Gets the user home directory path.
     *
     * @return bool|string
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
     * @return bool
     */
    public function isDevelopment();

    /**
     * Checks if running in production mode.
     *
     * @return bool
     */
    public function isProduction();

    /**
     * Checks if running in windows platform.
     *
     * @return bool
     */
    public function isPlatformWindows();
}
