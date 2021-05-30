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
     */
    public function startOverwrite(array $options = []): void;

    /**
     * Disables output overwriting.
     */
    public function endOverwrite(): void;

    /**
     * Check if overwritable output is enabled.
     */
    public function isOverwritable(): bool;
}
