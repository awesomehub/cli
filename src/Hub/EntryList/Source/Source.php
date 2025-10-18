<?php

declare(strict_types=1);

namespace Hub\EntryList\Source;

/**
 * Represents an EntryList Source.
 */
class Source implements SourceInterface
{
    protected mixed $data;
    protected array $options;

    public function __construct(protected string $type, mixed $data, array $options = [])
    {
        $this->data = $data;
        $this->options = $options;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasOption(string $key): bool
    {
        return \array_key_exists($key, $this->options);
    }

    public function getOption(string $key, $default = null): mixed
    {
        if (!$this->hasOption($key)) {
            return $default;
        }

        return $this->options[$key];
    }
}
