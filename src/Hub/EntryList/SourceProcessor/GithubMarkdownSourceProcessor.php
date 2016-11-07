<?php

namespace Hub\EntryList\SourceProcessor;

use Hub\EntryList\Source\Source;
use Hub\EntryList\Source\SourceInterface;
use League\CommonMark as CommonMark;

/**
 * Processes github markdown and outputs new entries.
 */
class GithubMarkdownSourceProcessor implements SourceProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(SourceInterface $source, \Closure $callback)
    {
        $markdown = $source->getData();
        if (empty($markdown)) {
            throw new \RuntimeException('Failed processing an empty markdown data');
        }

        $environment = CommonMark\Environment::createCommonMarkEnvironment();
        $parser      = new CommonMark\DocParser($environment);
        $document    = $parser->parse($markdown);

        $category         = '';
        $categoryNames    = $source->getOption('renameCategories', []);
        $ignoreCategories = $source->getOption('ignoreCategories', []);
        $insideListBlock  = false;

        $urls = [];
        $walker = $document->walker();
        while ($event = $walker->next()) {
            $node = $event->getNode();
            if ($node instanceof CommonMark\Block\Element\Heading && $event->isEntering()) {
                $category = $node->getStringContent();
                $category = $categoryNames[$category] ?? $category;
                continue;
            }

            if ($node instanceof CommonMark\Block\Element\ListBlock) {
                $insideListBlock = $event->isEntering();
                continue;
            }

            if ($node instanceof CommonMark\Inline\Element\Link && $event->isEntering() && $insideListBlock) {
                if (in_array($category, $ignoreCategories, true)) {
                    continue;
                }

                $urls[$category][] = $node->getUrl();
            }
        }

        $options = $source->getOptions();
        unset($options['renameCategories'], $options['ignoreCategories']);

        $skipCategory = isset($options['categories']['*']);
        $sources = [];
        foreach ($urls as $cat => $list){
            // Don't overwrite user-defined options
            // This is to allow user to map all source entries to one one category
            if(!$skipCategory){
                $options['categories']['*'] = $cat;
            }

            $sources[] = new Source('url.list', $list, $options);
        }

        return $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(SourceInterface $source)
    {
        return $source->getType() === 'github.markdown'
            ? self::ACTION_PARTIAL_PROCESSING
            : self::ACTION_SKIP;
    }
}
