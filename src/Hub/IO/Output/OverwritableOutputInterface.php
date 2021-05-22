<?php

namespace Hub\IO\Output;

use Symfony\Component\Console;

/**
 * Interface for an OverwritableOutput handler.
 */
interface OverwritableOutputInterface extends Console\Output\OutputInterface
{
    /**
     * Enables output overwriting.
     *
     * @return bool Previous state
     */
    public function startOverwrite(array $options = []);

    /**
     * Disables output overwriting.
     */
    public function endOverwrite();

    /**
     * Check if overwritable output is enabled.
     */
    public function isOverwritable();
}
