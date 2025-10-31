<?php

declare(strict_types=1);

namespace Hub\Build;

/**
 * Interface for a BuildFactory.
 */
interface BuildFactoryInterface
{
    /**
     * Creates a new build.
     *
     * @param null|string $path   Build path
     * @param bool        $hashed Whether it's a hashed build
     */
    public function create(?string $path = null, bool $hashed = true): BuildInterface;

    /**
     * Caches a build.
     */
    public function cache(BuildInterface $build): void;

    /**
     * Gets the currently cached build (most probably the last released build).
     */
    public function getCached(): ?BuildInterface;

    /**
     * Gets the current build.
     */
    public function getCurrent(): ?BuildInterface;
}
