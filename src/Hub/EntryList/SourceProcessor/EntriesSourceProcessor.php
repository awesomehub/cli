<?php

namespace Hub\EntryList\SourceProcessor;

use Hub\Entry\Factory\TypeEntryFactoryInterface;
use Hub\EntryList\Source\SourceInterface;
use Hub\Exceptions\EntryCreationFailedException;

/**
 * Creates entry objects from raw entries data.
 */
class EntriesSourceProcessor implements SourceProcessorInterface
{
    public function __construct(protected TypeEntryFactoryInterface $entryFactory)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(SourceInterface $source, \Closure $callback = null)
    {
        $entries = $source->getData();
        if (!\is_array($entries)) {
            throw new \UnexpectedValueException(sprintf('Unexpected entries source data type; Expected [array] but got [%s]', \gettype($entries)));
        }

        foreach ($entries as $i => $entry) {
            if (!isset($entry['type'], $entry['data']) || !\is_array($entry['data'])) {
                throw new \RuntimeException(sprintf('Incorrect entry schema at index[%d]', $i));
            }

            $callback(self::ON_STATUS_UPDATE, [
                'type' => 'info',
                'message' => sprintf('Attempting to create an entry from data at index[%d]', $i),
            ]);

            try {
                $entryInstance = $this->entryFactory::create($entry['type'], $entry['data']);
            } catch (EntryCreationFailedException $e) {
                $callback(self::ON_STATUS_UPDATE, [
                    'type' => 'error',
                    'message' => sprintf('Ignoring entry at index[%d]; %s', $i, $e->getMessage()),
                ]);

                continue;
            }

            $callback(self::ON_ENTRY_CREATED, $entryInstance);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(SourceInterface $source)
    {
        return 'entries' === $source->getType()
            ? self::ACTION_PROCESSING
            : self::ACTION_SKIP;
    }
}
