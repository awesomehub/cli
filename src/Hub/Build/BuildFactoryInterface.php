<?php

namespace Hub\Build;

/**
 * Interface for a BuildFactory.
 */
interface BuildFactoryInterface
{
    /**
     * Creates a new build.
     *
     * @return BuildInterface
     */
    public function create();

    /**
     * Caches a build.
     */
    public function cache(BuildInterface $build);

    /**
     * Gets the currently cached build (most probably the last released build).
     *
     * @return BuildInterface
     */
    public function getCached();

    /**
     * Gets the current build.
     *
     * @return BuildInterface
     */
    public function getCurrent();
}
