<?php

namespace Hub\EntryList\SourceProcessor;

use Hub\Entry\Factory\UrlEntryFactoryInterface;
use Hub\EntryList\Source\SourceInterface;
use Hub\Exceptions\EntryCreationFailedException;

/**
 * Processes a list of urls and outputs new entries.
 */
class UrlListSourceProcessor implements SourceProcessorInterface
{
    /**
     * @var UrlEntryFactoryInterface;
     */
    protected $entryFactory;

    /**
     * Constructor.
     *
     * @param UrlEntryFactoryInterface $entryFactory
     */
    public function __construct(UrlEntryFactoryInterface $entryFactory)
    {
        $this->entryFactory = $entryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(SourceInterface $source, \Closure $callback = null)
    {
        $urls = $source->getData();
        if (!is_array($urls)) {
            throw new \UnexpectedValueException(sprintf(
                'Unexpected source data type; Expected [array] but got [%s]', gettype($urls)
            ));
        }

        foreach ($urls as $i => $url) {
            $callback(self::ON_STATUS_UPDATE, [
                'type' => 'info',
                'message' => sprintf("Attempting to create an entry from url '%s'", $url)
            ]);
            try {
                $output = $this->entryFactory->create($url);
            } catch (EntryCreationFailedException $e) {
                $callback(self::ON_STATUS_UPDATE, [
                    'type' => 'error',
                    'message' => sprintf("Ignoring url '%s'; %s", $url, $e->getMessage())
                ]);
                continue;
            }

            foreach ($output as $entry) {
                $callback(self::ON_ENTRY_CREATED, $entry);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(SourceInterface $source)
    {
        return $source->getType() === 'url.list'
            ? self::ACTION_PROCESSING
            : self::ACTION_SKIP;
    }
}
