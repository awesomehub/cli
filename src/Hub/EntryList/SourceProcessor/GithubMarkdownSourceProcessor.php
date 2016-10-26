<?php

namespace Hub\EntryList\SourceProcessor;

use League\CommonMark as CommonMark;
use Http\Client\Common\HttpMethodsClient;
use Hub\Entry\Factory\UrlEntryFactoryInterface;
use Hub\Exceptions\SourceProcessorFailedException;
use Hub\Exceptions\EntryCreationFailedException;

/**
 * Processes github markdown and outputs new entries.
 */
class GithubMarkdownSourceProcessor implements SourceProcessorInterface
{
    /**
     * @var UrlEntryFactoryInterface;
     */
    protected $entryFactory;

    /**
     * @var HttpMethodsClient;
     */
    protected $http;

    /**
     * Sets the logger and the entry factory.
     *
     * @param UrlEntryFactoryInterface $entryFactory
     * @param HttpMethodsClient        $httpClient
     */
    public function __construct(UrlEntryFactoryInterface $entryFactory, HttpMethodsClient $httpClient)
    {
        $this->entryFactory = $entryFactory;
        $this->http         = $httpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $source, \Closure $callback = null)
    {
        /*
         * @var string $type
         * @var array $data
         * @var array $options
         */
        extract($source);

        if ($type === self::INPUT_MARKDOWN_URL) {
            try {
                $markdown = $this->fetchMarkdownUrl($data);
            } catch (\Exception $e) {
                throw new SourceProcessorFailedException(sprintf("Failed fetching url '%s'; %s", $data, $e->getMessage()));
            }
        } else {
            $markdown = $data;
        }

        if (empty($markdown)) {
            throw new SourceProcessorFailedException('Failed processing an empty markdown source.');
        }

        $environment = CommonMark\Environment::createCommonMarkEnvironment();
        $parser      = new CommonMark\DocParser($environment);
        $document    = $parser->parse($markdown);

        $entries         = [];
        $category        = $options['category'] ?? 'Uncategorized';
        $insideListBlock = false;

        $walker = $document->walker();
        while ($event = $walker->next()) {
            $node = $event->getNode();
            if ($node instanceof CommonMark\Block\Element\Heading && $event->isEntering() && empty($options['category'])) {
                $category = $node->getStringContent();
                $category = $options['categoryNames'][$category] ?? $category;
                continue;
            }

            if ($node instanceof CommonMark\Block\Element\ListBlock) {
                $insideListBlock = $event->isEntering();
                continue;
            }

            if ($node instanceof CommonMark\Inline\Element\Link && $event->isEntering() && $insideListBlock) {
                if (isset($options['ignoreCategories']) && in_array($category, $options['ignoreCategories'], true)) {
                    continue;
                }

                $url = $node->getUrl();
                try {
                    $callback && $callback(
                        self::EVENT_ENTRY_CREATE, $url,
                        sprintf("Trying to create an entry from url '%s'", $url)
                    );

                    $output = $this->entryFactory->create($url);
                } catch (EntryCreationFailedException $e) {
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

                    $entries[$category] = isset($entries[$category]) && is_array($entries[$category])
                        ? array_merge($entries[$category], $output)
                        : $output;
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
        return in_array($source['type'], [self::INPUT_MARKDOWN, self::INPUT_MARKDOWN_URL], true);
    }

    /**
     * Fetch the markdown string from an url.
     *
     * @param $url
     *
     * @throws \Exception When http request fails
     *
     * @return string
     */
    protected function fetchMarkdownUrl($url)
    {
        $response = $this->http->get($url);

        return (string) $response->getBody();
    }
}
