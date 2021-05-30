<?php

namespace Hub\Environment;

/**
 * Responsible for handling environmental aspects.
 */
class Environment implements EnvironmentInterface
{
    protected string $bin;
    protected string $mode;

    public function __construct(string $mode = null)
    {
        $this->setBin();
        $this->setMode($mode);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $varname): bool|array|string
    {
        return getenv($varname);
    }

    /**
     * {@inheritdoc}
     */
    public function getBin(): string
    {
        return $this->bin;
    }

    /**
     * {@inheritdoc}
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserHome(): ?string
    {
        // Check if on Windows platform
        if ($this->isPlatformWindows()) {
            $envAppData = (string) $this->get('APPDATA');
            if (!$envAppData) {
                return null;
            }

            return rtrim(str_replace('/', '\\', $envAppData), '\\');
        }

        $envHome = (string) $this->get('HOME');
        if (!$envHome) {
            return null;
        }

        return rtrim(str_replace('\\', '/', $envHome), '/');
    }

    /**
     * {@inheritdoc}
     */
    public function isDevelopment(): bool
    {
        return self::DEVELOPMENT === $this->mode;
    }

    /**
     * {@inheritdoc}
     */
    public function isProduction(): bool
    {
        return self::PRODUCTION === $this->mode;
    }

    /**
     * {@inheritdoc}
     */
    public function isPlatformWindows(): bool
    {
        return \defined('PHP_WINDOWS_VERSION_BUILD');
    }

    /**
     * Sets the current script path.
     */
    protected function setBin(): void
    {
        $this->bin = realpath($_SERVER['argv'][0]);
    }

    /**
     * Sets the environment mode, tries to auto guess if null.
     *
     * @throws \InvalidArgumentException
     */
    protected function setMode(string $mode = null): void
    {
        if ($mode) {
            if (!\in_array($mode, [self::DEVELOPMENT, self::PRODUCTION], true)) {
                throw new \InvalidArgumentException("Invalid environment mode supplied '{$mode}'.");
            }

            $this->mode = $mode;

            return;
        }

        // Check if we are inside phar
        if (str_starts_with(__FILE__, 'phar:')) {
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
