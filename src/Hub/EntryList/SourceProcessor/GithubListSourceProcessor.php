<?php

declare(strict_types=1);

namespace Hub\EntryList\SourceProcessor;

use Http\Client\Common\HttpMethodsClient;
use Hub\EntryList\Source\Source;
use Hub\EntryList\Source\SourceInterface;

/**
 * fetches github list markdown url and pass it to the github markdown processor.
 */
class GithubListSourceProcessor implements SourceProcessorInterface
{
    public function __construct(protected HttpMethodsClient $http)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(SourceInterface $source, \Closure $callback = null)
    {
        $url = $source->getData();
        if (preg_match('/^(?!https?:\/\/)([^\/]+)\/(.*)$/', $source->getData(), $matches)) {
            $url = sprintf(
                'https://raw.githubusercontent.com/%s/%s/master/README.md',
                $matches[1],
                $matches[2]
            );
        }

        $callback(self::ON_STATUS_UPDATE, [
            'type' => 'info',
            'message' => sprintf("Fetching '%s'", $url),
        ]);

        try {
            $response = $this->http->get($url);
            $markdown = (string) $response->getBody();
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf("Failed fetching url '%s'; %s", $url, $e->getMessage()), $e->getCode(), $e);
        }

        return new Source('github.markdown', $markdown, $source->getOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(SourceInterface $source)
    {
        return 'github.list' === $source->getType()
            ? self::ACTION_PARTIAL_PROCESSING
            : self::ACTION_SKIP;
    }
}
