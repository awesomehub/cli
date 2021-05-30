<?php

namespace Hub;

use Hub\Environment\EnvironmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interface for a Kernel.
 */
interface KernelInterface
{
    /**
     * Boots the kernel.
     */
    public function boot(): void;

    /**
     * Shutdowns the kernel.
     */
    public function shutdown(): void;

    /**
     * Checks whether the kernel is booted up.
     */
    public function isBooted(): bool;

    /**
     * Gets the current container.
     */
    public function getContainer(): ContainerInterface;

    /**
     * Gets the environment.
     *
     * @return EnvironmentInterface The current environment
     */
    public function getEnvironment(): EnvironmentInterface;
}
