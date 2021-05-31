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
     */
    public function create(): BuildInterface;

    /**
     * Caches a build.
     */
    public function cache(BuildInterface $build): void;

    /**
     * Gets the currently cached build (most probably the last released build).
     */
    public function getCached(): BuildInterface | null;

    /**
     * Gets the current build.
     */
    public function getCurrent(): BuildInterface | null;
}
