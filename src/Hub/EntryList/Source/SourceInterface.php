<?php

declare(strict_types=1);

namespace Hub\EntryList\Source;

/**
 * Interface for a Source.
 */
interface SourceInterface
{
    /**
     * Gets the source type.
     */
    public function getType(): string;

    /**
     * Gets the source data.
     */
    public function getData(): mixed;

    /**
     * Gets all source options.
     */
    public function getOptions(): array;

    /**
     * Gets an single option.
     */
    public function getOption(string $key, mixed $default = null): mixed;

    /**
     * Checks whether a source has an option.
     */
    public function hasOption(string $key): bool;
}
