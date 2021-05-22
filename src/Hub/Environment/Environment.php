<?php

namespace Hub\Environment;

/**
 * Responsible for handling environmental aspects.
 */
class Environment implements EnvironmentInterface
{
    /**
     * @var string
     */
    protected $bin;

    /**
     * @var string
     */
    protected $mode;

    /**
     * Constructor.
     *
     * @param $mode string Environment mode
     */
    public function __construct($mode = null)
    {
        $this->setBin();
        $this->setMode($mode);
    }

    /**
     * {@inheritdoc}
     */
    public function get($varname)
    {
        return getenv($varname);
    }

    /**
     * {@inheritdoc}
     */
    public function getBin()
    {
        return $this->bin;
    }

    /**
     * {@inheritdoc}
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserHome()
    {
        // Check if on Windows platform
        if ($this->isPlatformWindows()) {
            $envAppData = $this->get('APPDATA');
            if (!$envAppData) {
                return false;
            }

            return rtrim(strtr($envAppData, '/', '\\'), '\\');
        }

        $envHome = $this->get('HOME');
        if (!$envHome) {
            return false;
        }

        return rtrim(strtr($envHome, '\\', '/'), '/');
    }

    /**
     * {@inheritdoc}
     */
    public function isDevelopment()
    {
        return self::DEVELOPMENT === $this->mode;
    }

    /**
     * {@inheritdoc}
     */
    public function isProduction()
    {
        return self::PRODUCTION === $this->mode;
    }

    /**
     * {@inheritdoc}
     */
    public function isPlatformWindows()
    {
        return \defined('PHP_WINDOWS_VERSION_BUILD');
    }

    /**
     * Sets the current script path.
     */
    protected function setBin()
    {
        $this->bin = realpath($_SERVER['argv'][0]);
    }

    /**
     * Sets the environment mode, tries to autguess if null.
     *
     * @param null|string $mode
     *
     * @throws \InvalidArgumentException
     */
    protected function setMode($mode = null)
    {
        if ($mode) {
            if (!\in_array($mode, [self::DEVELOPMENT, self::PRODUCTION])) {
                throw new \InvalidArgumentException("Invalid environment mode supplied '{$mode}'.");
            }

            $this->mode = $mode;

            return;
        }

        // Check if we are inside phar
        if ('phar:' === substr(__FILE__, 0, 5)) {
            $this->mode = self::PRODUCTION;

            return;
        }

        // Check if ENV variable is defined
        if ($envMode = getenv('ENV')) {
            if (\in_array(strtolower($envMode), ['development', 'dev'])) {
                $this->mode = self::DEVELOPMENT;
            } else {
                $this->mode = self::PRODUCTION;
            }

            return;
        }

        // Check if a git repo is present
        if (file_exists(\dirname($this->getBin(), 2).\DIRECTORY_SEPARATOR.'.git')) {
            $this->mode = self::DEVELOPMENT;

            return;
        }

        // Fallback to production
        $this->mode = self::PRODUCTION;
    }
}
