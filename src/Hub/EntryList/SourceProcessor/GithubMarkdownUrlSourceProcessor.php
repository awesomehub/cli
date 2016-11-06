<?php

namespace Hub\EntryList\SourceProcessor;

use Hub\EntryList\Source\Source;
use Hub\EntryList\Source\SourceInterface;
use Http\Client\Common\HttpMethodsClient;

/**
 * fetches github markdown url and pass it to the github markdown processor.
 */
class GithubMarkdownUrlSourceProcessor implements SourceProcessorInterface
{
    /**
     * @var HttpMethodsClient;
     */
    protected $http;

    /**
     * Sets the logger and the entry factory.
     *
     * @param HttpMethodsClient $httpClient
     */
    public function __construct(HttpMethodsClient $httpClient)
    {
        $this->http = $httpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function process(SourceInterface $source, \Closure $callback = null)
    {
        $url = $source->getData();
        if(preg_match('/^(?!https?:\/\/)([^\/]+)\/(.*)$/', $source->getData(), $matches)) {
            $url = sprintf(
                'https://raw.githubusercontent.com/%s/%s/master/README.md',
                $matches[1],
                $matches[2]
            );
        }

        $callback(self::ON_STATUS_UPDATE, [
            'type' => 'info',
            'message' => sprintf("Fetching '%s'", $url)
        ]);
        try {
            $response = $this->http->get($url);
            $markdown = (string) $response->getBody();
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf("Failed fetching url '%s'; %s", $url, $e->getMessage()));
        }

        return new Source('github.markdown', $markdown, $source->getOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(SourceInterface $source)
    {
        return $source->getType() === 'github.markdown.url'
            ? self::ACTION_PARTIAL_PROCESSING
            : self::ACTION_SKIP;
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
