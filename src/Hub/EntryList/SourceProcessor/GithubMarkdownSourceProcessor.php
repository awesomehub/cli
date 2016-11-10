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
     * @var array
     */
    protected $rules = [];

    /**
     * @var array
     */
    protected $tree = [];

    /**
     * @var array
     */
    protected $pathMatches = [];

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

        // Load category rules definitions
        $this->loadlistRules($source->getOption('listCategories', []));

        $category        = '';
        $categoryRules   = null;
        $insideListBlock = false;

        $urls   = [];
        $walker = $document->walker();
        while ($event = $walker->next()) {
            $node = $event->getNode();
            if ($node instanceof CommonMark\Block\Element\Heading && $event->isEntering()) {
                $category      = $node->getStringContent();
                $categoryRules = $this->getCategoryRules($category, $node->getLevel());
                $category      = isset($categoryRules['rename'])
                    ? $categoryRules['rename']
                    : $category;
                continue;
            }

            if ($node instanceof CommonMark\Block\Element\ListBlock) {
                $insideListBlock = $event->isEntering();
                continue;
            }

            if ($node instanceof CommonMark\Inline\Element\Link && $event->isEntering() && $insideListBlock) {
                if (!empty($categoryRules['ignore'])) {
                    continue;
                }

                $urls[$category][] = $node->getUrl();
            }
        }

        // Ensure no rules has been skipped
        // This is to ensure the source is up to date
        $this->checkSkippedRules();

        $options = $source->getOptions();
        unset($options['listCategories']);

        $skipCategory = isset($options['categories']['*']);
        $sources      = [];
        foreach ($urls as $cat => $list) {
            // Don't overwrite user-defined options
            // This is to allow user to map all source entries to one one category
            if (!$skipCategory) {
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

    /**
     * Builds list rules from config.
     *
     * @param array $rules
     */
    protected function loadlistRules(array $rules)
    {
        foreach ($rules as $path => $rule) {
            $matches = [];
            if (!preg_match_all('/(^|\/|\:)H([0-9])\((.*?)\)/', $path, $matches) | empty($matches[0])) {
                throw new \RuntimeException(sprintf("Invalid category path regex '%s'", $path));
            }

            $entry = [];
            for ($i = 0; $i < count($matches[0]); ++$i) {
                $entry['pathRaw'] = $path;
                $entry['path'][]  = [
                    'operator' => $matches[1][$i],
                    'node' => [
                        'level'    => (int) $matches[2][$i],
                        'category' => !empty($rule['cs'])
                            ? $matches[3][$i]
                            : strtolower($matches[3][$i]),
                    ]
                ];
            }
            $entry['rules'] = $rule;

            $this->rules[] = $entry;
        }
    }

    /**
     * Get a list category rules if defined.
     *
     * @param $category
     * @param $level
     *
     * @return array|null
     */
    protected function getCategoryRules($category, $level)
    {
        // Add the category to to the tree
        $this->tree['cs'][] = ['level' => $level, 'category' => $category];
        $this->tree['ci'][] = ['level' => $level, 'category' => strtolower($category)];

        // Search for a matching path
        foreach ($this->rules as $rule) {
            $tree = !empty($rule['rules']['cs'])
                ? $this->tree['cs']
                : $this->tree['ci'];
            $pathSeg  = end($rule['path']);
            $treeNode = end($tree);
            if ($pathSeg['node'] !== $treeNode) {
                continue;
            }

            do {
                $continue = false;
                switch ($pathSeg['operator']) {
                    // Matches parent node
                    case '/':
                        $maxLevel = $pathSeg['node']['level'];
                        $pathSeg  = prev($rule['path']);
                        while ($treeNode = prev($tree)) {
                            if ($treeNode['level'] < $maxLevel && $pathSeg['node'] === $treeNode) {
                                $continue = true;
                                break;
                            }
                        }
                        break;

                    // Matches sibling node
                    case ':':
                        $pathSeg  = prev($rule['path']);
                        $treeNode = prev($tree);
                        $continue = $pathSeg['node'] === $treeNode;
                        break;

                    // Marks the end of the path
                    // Technically its the start of the path but we're walking backwards
                    case '':
                        $this->pathMatches[] = $rule['pathRaw'];

                        return $rule['rules'];

                    default:
                        throw new \LogicException(sprintf("Unexpected path operator '%s'", $pathSeg['operator']));
                }
            } while ($continue);
        }

        return null;
    }

    /**
     * Ensures all defined category paths has been matched.
     *
     * @throws \RuntimeException
     */
    protected function checkSkippedRules()
    {
        $skippedPaths = array_diff(array_column($this->rules, 'pathRaw'), $this->pathMatches);
        if (count($skippedPaths) > 0) {
            throw new \RuntimeException(sprintf("Unable to match category path%s '%s'",
                count($skippedPaths) > 1 ? 's' : '',
                implode(', ', $skippedPaths)
            ));
        }
    }
}
