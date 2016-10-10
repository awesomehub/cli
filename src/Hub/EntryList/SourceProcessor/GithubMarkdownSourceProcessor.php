<?php
namespace Hub\EntryList\SourceProcessor;

use Psr\Log\LoggerInterface;
use Http\Client\Common\HttpMethodsClient;
use League\CommonMark as CommonMark;
use Hub\Entry\Factory\EntryFactoryInterface;
use Hub\Exceptions\EntryCreationFailedException;

/**
 * Processes github markdown and outputs new entries.
 *
 * @package AwesomeHub
 */
class GithubMarkdownSourceProcessor implements SourceProcessorInterface
{
    /**
     * @var EntryFactoryInterface $entryFactory;
     */
    protected $entryFactory;

    /**
     * @var HttpMethodsClient $http;
     */
    protected $http;

    /**
     * Sets the logger and the entry factory.
     *
     * @param EntryFactoryInterface $entryFactory
     * @param HttpMethodsClient $httpClient
     */
    public function __construct(EntryFactoryInterface $entryFactory, HttpMethodsClient $httpClient)
    {
        $this->entryFactory = $entryFactory;
        $this->http = $httpClient;
    }

    /**
     * @inheritdoc
     */
    public function process(LoggerInterface $logger, array $source)
    {
        if($source['type'] === self::INPUT_MARKDOWN_URL){
            try {
                $markdown = $this->fetchMarkdownUrl($source['data']);
            }
            catch (\Exception $e){
                $logger->error("Failed fetching url '{$source['data']}'; {$e->getMessage()}");
                return false;
            }
        }
        else {
            $markdown = $source['data'];
        }

        if(empty($markdown)){
            $logger->error("Failed processing an empty markdown source.");
            return false;
        }

        $environment = CommonMark\Environment::createCommonMarkEnvironment();
        $parser = new CommonMark\DocParser($environment);
        $document = $parser->parse($markdown);

        $enteries = [];
        $category = 'Uncategorized';
        $insideListBlock = false;

        $walker = $document->walker();
        while ($event = $walker->next()) {
            $node = $event->getNode();
            if($node instanceof CommonMark\Block\Element\Heading && $event->isEntering()){
                $category = $node->getStringContent();
                continue;
            }

            if($node instanceof CommonMark\Block\Element\ListBlock){
                $insideListBlock = $event->isEntering();
                continue;
            }

            if($node instanceof CommonMark\Inline\Element\Link && $event->isEntering() && $insideListBlock){
                $url = $node->getUrl();
                try {
                    $output = $this->entryFactory->create($url);
                }
                catch (EntryCreationFailedException $e) {
                    $logger->warning("Ignoring url '$url'; " . $e->getMessage());
                    continue;
                }

                if(sizeof($output) > 0){
                    $enteries[$category] = isset($enteries[$category]) && is_array($enteries[$category])
                        ? array_merge($enteries[$category], $output)
                        : $output ;
                }
            }
        }

        return $enteries;
    }

    /**
     * @inheritdoc
     */
    public function supports(array $source)
    {
        return in_array($source['type'], [self::INPUT_MARKDOWN, self::INPUT_MARKDOWN_URL], true);
    }

    /**
     * Fetch the markdown string from an url.
     *
     * @param $url
     * @return string
     * @throws \Exception When http request fails
     */
    protected function fetchMarkdownUrl($url)
    {
        $response = $this->http->get($url);
        return (string) $response->getBody();
    }
}
