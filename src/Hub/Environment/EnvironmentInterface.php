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
     */
    public function get(string $varname): mixed;

    /**
     * Gets the user home directory path.
     */
    public function getUserHome(): ?string;

    /**
     * Gets the current script path.
     */
    public function getBin(): string;

    /**
     * Gets the environment mode.
     */
    public function getMode(): string;

    /**
     * Checks if running in development mode.
     */
    public function isDevelopment(): bool;

    /**
     * Checks if running in production mode.
     */
    public function isProduction(): bool;

    /**
     * Checks if running in windows platform.
     */
    public function isPlatformWindows(): bool;
}
