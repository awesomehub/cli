<?php

namespace Hub;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interface for a Kernel.
 */
interface KernelInterface
{
    /**
     * Boots the kernel.
     */
    public function boot();

    /**
     * Shutdowns the kernel.
     */
    public function shutdown();

    /**
     * Checks whether the kernel is booted up.
     *
     * @return bool
     */
    public function isBooted();

    /**
     * Gets the current container.
     *
     * @return ContainerInterface
     */
    public function getContainer();

    /**
     * Gets the environment.
     *
     * @return string The current environment
     */
    public function getEnvironment();
}
