<?php

namespace Hub\EntryList\SourceProcessor;

use Hub\Entry\Factory\TypeEntryFactoryInterface;
use Hub\Exceptions\SourceProcessorFailedException;
use Hub\Exceptions\EntryCreationFailedException;

/**
 * Creates entry objects from raw entries data.
 */
class EntriesSourceProcessor implements SourceProcessorInterface
{
    /**
     * @var TypeEntryFactoryInterface;
     */
    protected $entryFactory;

    /**
     * Sets the logger and the entry factory.
     *
     * @param TypeEntryFactoryInterface $entryFactory
     */
    public function __construct(TypeEntryFactoryInterface $entryFactory)
    {
        $this->entryFactory = $entryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $source, \Closure $callback = null)
    {
        /* @var string $type */
        /* @var array  $data */
        extract($source);

        if (!is_array($data)) {
            throw new SourceProcessorFailedException(sprintf(
                'Unexpected source data type; Expected [array] but got [%s]', gettype($data)
            ));
        }

        $entries = [];
        foreach ($data as $category => $categoryEntries) {
            if (!is_array($categoryEntries)) {
                throw new SourceProcessorFailedException(sprintf(
                    'Unexpected source data type at [%s]; Expected [array] but got [%s]', $category, gettype($categoryEntries)
                ));
                continue;
            }

            $entries[$category] = [];
            foreach ($categoryEntries as $index => $entry) {
                if (!isset($entry['type']) || !isset($entry['data']) || !is_array($entry['data'])) {
                    $callback && $callback(
                        self::EVENT_ENTRY_FAILED,
                        sprintf('[%s][%d]', $category, $index),
                        sprintf('Incorrect entry schema at [%s][%d]', $category, $index)
                    );

                    continue;
                }

                try {
                    $callback && $callback(
                        self::EVENT_ENTRY_CREATE,
                        sprintf('[%s][%d]', $category, $index),
                        sprintf('Trying to create an entry from data [%s][%d]', $category, $index)
                    );

                    $entryInstance = $this->entryFactory->create($entry['type'], $entry['data']);
                } catch (EntryCreationFailedException $e) {
                    $callback && $callback(
                        self::EVENT_ENTRY_FAILED,
                        sprintf('[%s][%d]', $category, $index),
                        sprintf('Entry creating failed; %s', $e->getMessage())
                    );

                    continue;
                }

                $callback && $callback(
                    self::EVENT_ENTRY_SUCCESS,
                    'Entry created successfully',
                    sprintf('[%s][%d]', $category, $index)
                );

                $entries[$category][] = $entryInstance;
            }
        }

        return $entries;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $source)
    {
        return $source['type'] === self::INPUT_ENTRIES;
    }
}
