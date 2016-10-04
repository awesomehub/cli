<?php
namespace Hub\Environment;

use Symfony\Component\Console\Input\InputInterface;
use Hub\Environment\Workspace\WorkspaceInterface;

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
     * Constructor.
     *
     * @param $input InputInterface
     * @param $mode string Environment mode
     */
    public function __construct(InputInterface $input, $mode = null);

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
     * Gets the currently active workspace.
     *
     * @return WorkspaceInterface
     */
    public function getWorkspace();

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
}
