<?php
namespace Hub\EntryList\SourceProcessor;

use Psr\Log\LoggerInterface;
use Hub\Entry\Factory\EntryFactoryInterface;
use Hub\Exceptions\EntryCreationFailedException;

/**
 * Processes a list of urls and outputs new entries.
 *
 * @package AwesomeHub
 */
class UrlsSourceProcessor implements SourceProcessorInterface
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
        foreach ($source['data'] as $category => $categoryUrls) {
            if(!is_array($categoryUrls)){
                throw new \InvalidArgumentException("Unexpected source data type at [$category]; Expected [array] but got [" . gettype($categoryUrls) . "]");
                continue;
            }

            $enteries[$category] = [];
            foreach ($categoryUrls as $index => $url) {
                try {
                    $output = $this->entryFactory->create($url);
                }
                catch (EntryCreationFailedException $e) {
                    $logger->warning("Ignoring source url '$url' at [$category][$index]; " . $e->getMessage());
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
        return $source['type'] === self::INPUT_URLS;
    }
}
