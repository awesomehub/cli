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
    protected array $rules = [];
    protected array $tree = [
        'cs' => [['level' => 0, 'category' => null]],
        'ci' => [['level' => 0, 'category' => null]],
    ];
    protected array $pathMatches = [];

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
        $parser = new CommonMark\DocParser($environment);
        $document = $parser->parse($markdown);

        // Load category rules definitions
        $this->loadlistRules($source->getOption('markdownCategories', []));

        $category = null;
        $categories_with_rules = [];
        $insideListBlock = false;

        $urls = [];
        $walker = $document->walker();
        while ($event = $walker->next()) {
            $node = $event->getNode();
            if ($node instanceof CommonMark\Block\Element\Heading && $event->isEntering()) {
                $textNode = $node->firstChild();
                $heading = $textNode instanceof CommonMark\Inline\Element\Text
                    ? trim($textNode->getContent())
                    : $node->getStringContent();
                $headingLevel = $node->getLevel();
                $rules = $this->getCategoryRules($heading, $headingLevel);
                // Category title can not have slashes, we use it for parent/child relationship
                $heading = preg_replace('/\s*\/+\s*/', ' & ', $heading);

                $category = [
                    'name' => $heading,
                    'level' => $headingLevel,
                    'rules' => $rules,
                ];

                while ($category_with_rules = end($categories_with_rules)) {
                    if ($category_with_rules && $category_with_rules['level'] >= $headingLevel) {
                        array_pop($categories_with_rules);

                        continue;
                    }

                    break;
                }

                if (null !== $rules) {
                    if (!isset($rules['recursive']) || !empty($rules['recursive'])) {
                        $categories_with_rules[] = $category;
                    }
                } elseif ($category_with_rules) {
                    $category = $category_with_rules;
                }

                if (!empty($category['rules']['rename'])) {
                    $category['name'] = str_replace('{name}', $heading, $category['rules']['rename']);
                }

                continue;
            }

            if ($node instanceof CommonMark\Block\Element\ListBlock) {
                $insideListBlock = $event->isEntering();

                continue;
            }

            if ($insideListBlock && $node instanceof CommonMark\Inline\Element\Link && $event->isEntering()) {
                if (!empty($category['rules']['ignore'])) {
                    continue;
                }

                $urls[$category['name']][] = $node->getUrl();
            }
        }

        // Ensure no rules has been skipped
        // This is to ensure the source is up to date
        $this->checkSkippedRules();

        $options = $source->getOptions();
        $options['exclude'] = $source->getOption('exclude', []);
        $options['exclude'][] = '\/awesome-';

        unset($options['markdownCategories']);

        $sources = [];
        foreach ($urls as $cat => $list) {
            $sourceOptions = $options;
            $sourceOptions['categories'][$cat] = '*';
            $sources[] = new Source('url.list', $list, $sourceOptions);
        }

        return $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(SourceInterface $source)
    {
        return 'github.markdown' === $source->getType()
            ? self::ACTION_PARTIAL_PROCESSING
            : self::ACTION_SKIP;
    }

    /**
     * Builds list rules from config.
     */
    protected function loadlistRules(array $rules): void
    {
        foreach ($rules as $path => $rule) {
            $matches = [];
            if (!preg_match_all('/(^|\/|:)H([\d])\(([^)]+?)\)/i', $path, $matches) || empty($matches[0])) {
                $matches = [];
                if (!preg_match_all('/(^|\/|:)H([\d])\[([^]]+?)\]/i', $path, $matches) || empty($matches[0])) {
                    throw new \RuntimeException(sprintf("Invalid category path regex '%s'", $path));
                }
            }

            $entry = [
                'pathRaw' => $path,
                'path' => [],
                'rules' => $rule,
            ];
            for ($i = 0; $i < \count($matches[0]); ++$i) {
                $operator = $matches[1][$i];
                $level = (int) $matches[2][$i];
                $category = $matches[3][$i];

                if ('/' === $operator && 0 === $i) {
                    $entry['path'][] = [
                        'operator' => '',
                        'node' => ['level' => 0, 'category' => null],
                    ];
                }

                $entry['path'][] = [
                    'operator' => $operator,
                    'node' => [
                        'level' => $level,
                        'category' => !empty($rule['isCaseSensitive'])
                            ? $category
                            : strtolower($category),
                    ],
                ];
            }

            $this->rules[] = $entry;
        }
    }

    /**
     * Get a list category rules if defined.
     */
    protected function getCategoryRules(string $category, int $level): ?array
    {
        // Add the category to to the tree
        $this->tree['cs'][] = ['level' => $level, 'category' => $category];
        $this->tree['ci'][] = ['level' => $level, 'category' => strtolower($category)];

        // Search for a matching path
        foreach ($this->rules as $rule) {
            $tree = !empty($rule['rules']['isCaseSensitive'])
                ? $this->tree['cs']
                : $this->tree['ci'];
            $pathSeg = end($rule['path']);
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
                        $pathSeg = prev($rule['path']);
                        while ($treeNode = prev($tree)) {
                            if ($treeNode['level'] < $maxLevel && $pathSeg['node'] === $treeNode) {
                                $continue = true;

                                break;
                            }
                        }

                        break;
                    // Matches sibling node
                    case ':':
                        $pathSeg = prev($rule['path']);
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
    protected function checkSkippedRules(): void
    {
        $skippedPaths = array_diff(array_column($this->rules, 'pathRaw'), $this->pathMatches);
        if (\count($skippedPaths) > 0) {
            throw new \RuntimeException(sprintf("Unable to match category path%s '%s'", \count($skippedPaths) > 1 ? 's' : '', implode(', ', $skippedPaths)));
        }
    }
}
