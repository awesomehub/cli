<?php
namespace Hub\EntryList\SourceProcessor;

use Psr\Log\LoggerInterface;
use Hub\Entry\Factory\EntryFactoryInterface;
use Hub\Exceptions\EntryCreationFailedException;

/**
 * Creates entry objects from raw entries data.
 *
 * @package AwesomeHub
 */
class EntriesSourceProcessor implements SourceProcessorInterface
{
    /**
     * @var EntryFactoryInterface $entryFactory;
     */
    protected $entryFactory;

    /**
     * Sets the logger and the entry factory.
     *
     * @param EntryFactoryInterface $entryFactory
     */
    public function __construct(EntryFactoryInterface $entryFactory)
    {
        $this->entryFactory = $entryFactory;
    }

    /**
     * @inheritdoc
     */
    public function process(LoggerInterface $logger, array $source)
    {
        if(!is_array($source['data'])){
            throw new \InvalidArgumentException("Unexpected source data type; Expected [array] but got [" . gettype($source['data']) . "]");
        }

        $entries = [];
        foreach ($source['data'] as $category => $categoryEntries) {
            if(!is_array($categoryEntries)){
                throw new \InvalidArgumentException("Unexpected source data type at [$category]; Expected [array] but got [" . gettype($categoryEntries) . "]");
                continue;
            }

            $enteries[$category] = [];
            foreach ($categoryEntries as $index => $entry) {
                if(!isset($entry['type']) || !isset($entry['data'])){
                    $logger->warning("Ignoring source entry at [$category][$index]; Incorrect entry schema.");
                    continue;
                }

                try {
                    $output = $this->entryFactory->create($entry);
                }
                catch (EntryCreationFailedException $e){
                    $logger->warning("Ignoring source entry at [$category][$index]; " . $e->getMessage());
                    continue;
                }

                if(sizeof($output) > 0){
                    $enteries[$category] = array_merge($enteries[$category], $output);
                }
            }
        }

        return $entries;
    }

    /**
     * @inheritdoc
     */
    public function supports(array $source)
    {
        return $source['type'] === self::INPUT_ENTRIES;
    }
}
