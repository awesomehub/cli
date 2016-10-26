<?php

namespace Hub\EntryList\SourceProcessor;

use Hub\Entry\Factory\UrlEntryFactoryInterface;
use Hub\Exceptions\SourceProcessorFailedException;
use Hub\Exceptions\EntryCreationFailedException;

/**
 * Processes a list of urls and outputs new entries.
 */
class UrlsSourceProcessor implements SourceProcessorInterface
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
    public function process(array $source, \Closure $callback = null)
    {
        /**
         * @var string $type
         * @var array $data
         */
        extract($source);

        if (!is_array($data)) {
            throw new SourceProcessorFailedException(sprintf(
                'Unexpected source data type; Expected [array] but got [%s]', gettype($data)
            ));
        }

        $entries = [];
        foreach ($data as $category => $categoryUrls) {
            if (!is_array($categoryUrls)) {
                throw new SourceProcessorFailedException(sprintf(
                    'Unexpected source data type at [%s]; Expected [array] but got [%s]', $category, gettype($categoryUrls)
                ));
                continue;
            }

            $enteries[$category] = [];
            foreach ($categoryUrls as $index => $url) {
                try {
                    $callback && $callback(
                        self::EVENT_ENTRY_CREATE, $url,
                        sprintf("Trying to create an entry from url '%s'", $url)
                    );

                    $output = $this->entryFactory->create($url);
                }
                catch (EntryCreationFailedException $e) {
                    $callback && $callback(
                        self::EVENT_ENTRY_FAILED, $url,
                        sprintf("Ignoring url '%s'; %s", $url, $e->getMessage())
                    );

                    continue;
                }

                if (sizeof($output) > 0) {
                    $callback && $callback(
                        self::EVENT_ENTRY_SUCCESS, $url,
                        sprintf("Processed url '%s' and got %d entry(s)", $url, sizeof($output))
                    );

                    $enteries[$category] = array_merge($enteries[$category], $output);
                }
            }
        }

        return $entries;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $source)
    {
        return $source['type'] === self::INPUT_URLS;
    }
}
