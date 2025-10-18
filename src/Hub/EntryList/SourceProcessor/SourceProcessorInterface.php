<?php

declare(strict_types=1);

namespace Hub\EntryList\SourceProcessor;

use Hub\EntryList\Source\SourceInterface;

/**
 * Interface for a SourceProcessor.
 */
interface SourceProcessorInterface
{
    /**
     * Fired when the process send a new status message.
     */
    public const ON_STATUS_UPDATE = 1;

    /**
     * Fired when an entry is created.
     */
    public const ON_ENTRY_CREATED = 2;

    /**
     * Causes the factory to move on to the next processor.
     */
    public const ACTION_SKIP = 0;

    /**
     * Causes the factory to exclusively use this processor to process the source.
     */
    public const ACTION_PROCESSING = 1;

    /**
     * Causes the factory to process the source with this processor then pass the result to the next processor.
     */
    public const ACTION_PARTIAL_PROCESSING = 2;

    /**
     * Processes the source and outputs new entry(s).
     *
     * @param \Closure $callback Should receive 2 args ($event, $payload)
     *
     * @return SourceInterface|SourceInterface[]|void On partial processing it returns a child source(s)
     *
     * @throws \Exception
     */
    public function process(SourceInterface $source, \Closure $callback);

    /**
     * Determines whether the processor supports the given source.
     *
     * @return int
     */
    public function getAction(SourceInterface $source);
}
