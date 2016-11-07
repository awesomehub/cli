<?php

namespace Hub\EntryList;

use Symfony\Component\Config as SymfonyConfig;
use Hub\IO\IOInterface;
use Hub\Entry\EntryInterface;
use Hub\EntryList\Source\SourceInterface;
use Hub\EntryList\SourceProcessor\SourceProcessorInterface;
use Hub\Entry\Resolver\EntryResolverInterface;
use Hub\Exceptions\EntryResolveFailedException;

/**
 * The Base List class.
 */
class EntryList implements EntryListInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var bool
     */
    protected $resolved = false;

    /**
     * Constructor.
     *
     * @param array $data List definition
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __construct(array $data)
    {
        try {
            $this->data = $this->verify($data);
        } catch (SymfonyConfig\Definition\Exception\Exception $e) {
            throw new \InvalidArgumentException("Unable to process the list definition data; {$e->getMessage()}.", 0, $e);
        }

        foreach ($this->data['sources'] as $i => $source) {
            $this->data['sources'][$i] = new Source\Source(
                $source['type'],
                $source['data'],
                $source['options']
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return strtolower($this->get('id'));
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key = null)
    {
        if (null === $key) {
            return $this->data;
        }

        if (!array_key_exists($key, $this->data)) {
            throw new \InvalidArgumentException(sprintf("Trying to get an undefined list data key '%s'", $key));
        }

        return $this->data[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value = null)
    {
        if (func_num_args() === 1) {
            if (!is_array($key)) {
                throw new \UnexpectedValueException(sprintf('Expected array but got %s', var_export($key, true)));
            }

            $this->data = $key;

            return;
        }

        $this->data[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function process(IOInterface $io, array $processors)
    {
        if (empty($processors)) {
            throw new \LogicException('Cannot process the list; No source processors has been provided.');
        }
        $logger = $io->getLogger();

        $logger->info('Processing list sources');
        $this->processSources($io, $processors);
        $logger->info(sprintf('Processed %d entry(s)', count($this->data['entries'])));

        $logger->info('Organizing categories');
        $nextCategoryId = 1;
        $categories     = [];
        foreach ($this->data['entries'] as $entry) {
            /* @var EntryInterface $entry */
            $entryType       = $entry->getType();
            $entryCategories = $entry->get('categories');
            if (empty($entryCategories)) {
                $entryCategories = ['Uncatagorized'];
            }
            $entryCategoryIds = [];
            foreach ($entryCategories as $categoryName) {
                $categoryPath = $this->getCategoryPath($categoryName) ?: [$categoryName];
                $parent       = 0;
                foreach ($categoryPath as $pathSegment) {
                    $idTitleMap = array_column($categories, 'title', 'id');
                    if (in_array($pathSegment, $idTitleMap, true)) {
                        $parent = $entryCategoryIds[] = array_search($pathSegment, $idTitleMap, true);
                        ++$categories[$parent]['count'][$entryType];
                        ++$categories[$parent]['count']['all'];
                        continue;
                    }

                    $categories[$nextCategoryId] = [
                        'id'     => $nextCategoryId,
                        'title'  => $pathSegment,
                        'parent' => $parent,
                        'count'  => [
                            'all'      => 1,
                            $entryType => 1,
                        ],
                    ];
                    $parent             = $nextCategoryId;
                    $entryCategoryIds[] = $nextCategoryId;
                    ++$nextCategoryId;
                }
            }

            $entry->set('categories', $entryCategoryIds);
        }

        $this->data['categories'] = $categories;
        $logger->info(sprintf('Organized %d category(s)', count($categories)));
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(IOInterface $io, array $resolvers, $force = false)
    {
        if (empty($resolvers)) {
            throw new \LogicException('Cannot resolve the list; No resolvers has been provided');
        }

        if (empty($this->data['entries'])) {
            throw new \LogicException('No entries to resolve');
        }

        $logger = $io->getLogger();

        $logger->info('Resolving list entries');
        $io->startOverwrite();
        $indicator = ' [ %%spinner%% ] Resolving entry#%d => %s (%%elapsed%%)';

        $i = $ir = $ic = 0;
        /* @var EntryInterface $entry */
        foreach ($this->data['entries'] as $id => $entry) {
            ++$i;
            $resolvedWith = false;
            $isCached     = false;
            /* @var EntryResolverInterface $resolver */
            foreach ($resolvers as $resolver) {
                if ($resolver->supports($entry)) {
                    $resolvedWith = $resolver;
                    $isCached     = $resolver->isCached($entry);
                    $io->write(sprintf($indicator, $i, $id));
                    try {
                        $resolver->resolve($entry, $force);
                    } catch (EntryResolveFailedException $e) {
                        $this->removeEntry($entry);
                        $logger->warning(sprintf("Failed resolving entry#%d [%s] with '%s'; %s", $i, $id, get_class($resolver), $e->getMessage()));
                        continue 2;
                    }

                    break;
                }
            }

            // Check if no resolver can resolve this entry
            if (false === $resolvedWith) {
                $this->removeEntry($entry);
                $logger->warning(sprintf("Ignoring entry#%d [%s] of type '%s'; None of the given resolvers supports it", $i, $id, get_class($entry)));
                continue;
            }

            if ($isCached) {
                ++$ic;
            }

            ++$ir;
        }

        $this->resolved = true;
        $logger->info(sprintf('Resolved %d/%d entry(s) with %d cached entry(s)',
            $ir, $i, $ic
        ));
        $io->endOverwrite();
    }

    /**
     * {@inheritdoc}
     */
    public function isResolved()
    {
        return $this->resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function removeEntry(EntryInterface $entry)
    {
        // Remove from entries
        $entries = $this->get('entries');
        unset($entries[$entry->getId()]);
        $this->set('entries', $entries);

        // Update cat counts
        $categories = $this->get('categories');
        foreach ($categories as $i => $category) {
            if (in_array($category['id'], $entry->get('categories'))) {
                --$categories[$i]['count']['all'];
                --$categories[$i]['count'][$entry->getType()];

                // Remove the category if it hs no entries
                if (1 > $categories[$i]['count']['all']) {
                    unset($categories[$i]);
                }
            }
        }
        $this->set('categories', $categories);
    }

    /**
     * Adds an entry to the list.
     *
     * @param EntryInterface  $entry
     * @param SourceInterface $source
     */
    protected function addEntry(EntryInterface $entry, SourceInterface $source)
    {
        $id = $entry->getId();
        if ($source->hasOption('exclude')) {
            foreach ($source->getOption('exclude', []) as $regex) {
                $regex = $regex[0] !== '/' ? "/${regex}/" : $regex;
                if (false === @preg_match($regex, null)) {
                    throw new \InvalidArgumentException(sprintf("Invalid exclude regex '%s'", $regex));
                }

                if (preg_match($regex, $id)) {
                    return;
                }
            }
        }

        // Check if an already existing entry exists
        if (isset($this->data['entries'][$id])) {
            $this->data['entries'][$id]->merge($entry->get());
            $entry = $this->data['entries'][$id];
        }

        $categories = [];
        if ($entry->has('categories')) {
            $categories = $entry->get('categories');
        }

        // Add source categories
        if ($source->hasOption('categories')) {
            foreach ($source->getOption('categories', []) as $regex => $sourceCategory) {
                $regex = $regex === '*' ? '.*' : $regex;
                $regex = $regex[0] !== '/' ? "/${regex}/" : $regex;
                if (false === @preg_match($regex, null)) {
                    throw new \InvalidArgumentException(sprintf("Invalid category regex '%s'", $regex));
                }

                if (preg_match($regex, $id)) {
                    if (!is_array($sourceCategory)) {
                        $sourceCategory = [$sourceCategory];
                    }
                    $categories = array_merge($categories, $sourceCategory);
                }
            }
        }

        $cleaned = [];
        foreach ($categories as $category) {
            // Strip non-utf chars
            $category = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $category);
            $category = trim($category);

            if(!empty($category)){
                $cleaned[] = $category;
            }
        }

        // Set the final categories
        $entry->set('categories', array_unique($cleaned));

        // Save the entry
        $this->data['entries'][$id] = $entry;
    }

    /**
     * Recursively processes list sources.
     *
     * @param IOInterface                $io
     * @param SourceProcessorInterface[] $processors
     * @param SourceInterface[]|null     $sources
     * @param int                        $depth
     */
    protected function processSources(IOInterface $io, array $processors, array $sources = [], $depth = 0)
    {
        $root      = 0 === $depth;
        $logger    = $io->getLogger();
        $depthStr  = $root ? '' : str_repeat('|_ ', $depth);
        $indicator = ' [ %%spinner%% ] %s (%%elapsed%%)';

        if ($root) {
            $io->startOverwrite();
            $sources = $this->data['sources'];
        }

        foreach ($sources as $index => $source) {
            $id            = ($root ? 'index='.$index.' ' : '').'type='.$source->getType();
            $processedWith = false;
            $callback      = function ($event, $payload) use ($source, $io, $indicator) {
                switch ($event) {
                    case SourceProcessorInterface::ON_STATUS_UPDATE:
                        if ($payload['type'] === 'error') {
                            $io->getLogger()->warning($payload['message']);
                            break;
                        }
                        $io->write(sprintf($indicator, $payload['message']));
                        break;

                    case SourceProcessorInterface::ON_ENTRY_CREATED:
                        $this->addEntry($payload, $source);
                        break;

                    default:
                        throw new \UnexpectedValueException(
                            sprintf("Unsupported source processor event '%s'", $event)
                        );
                }
            };

            foreach ($processors as $processor) {
                $processorName = basename(str_replace('\\', '/', get_class($processor)));
                switch ($processor->getAction($source)) {
                    case SourceProcessorInterface::ACTION_PARTIAL_PROCESSING:
                        $processedWith = $processor;
                        $logger->info(sprintf("%sProcessing source[%s] with '%s'", $depthStr, $id, $processorName));
                        try {
                            $childSources = $processor->process($source, $callback);
                        } catch (\Exception $e) {
                            $logger->critical(sprintf("Failed processing source[%s] with '%s'; %s",
                                $id, $processorName, $e->getMessage()
                            ));
                            continue;
                        }

                        if (!is_array($childSources)) {
                            $childSources = [$childSources];
                        }

                        if (count($childSources) === 0) {
                            $logger->warning(sprintf(
                                "No child sources from processing source[%s] with '%s'",
                                $id, $processorName
                            ));
                            continue;
                        }

                        $this->processSources($io, $processors, $childSources, $depth + 1);
                        break;

                    case SourceProcessorInterface::ACTION_PROCESSING:
                        $processedWith = $processor;
                        $logger->info(sprintf("%sProcessing source[%s] with '%s'", $depthStr, $id, $processorName));
                        try {
                            $processor->process($source, $callback);
                        } catch (\Exception $e) {
                            $logger->critical(sprintf("Failed processing source[%s] with '%s'; %s",
                                $id, $processorName, $e->getMessage()
                            ));
                        }
                        break;

                    case SourceProcessorInterface::ACTION_SKIP:
                        break;

                    default:
                        throw new \UnexpectedValueException(sprintf(
                            "Got an invalid processing mode from processor '%s'", get_class($processor)
                        ));
                }
            }

            // Check if no processor can process this source
            if (false === $processedWith) {
                $logger->critical(sprintf('Ignoring source[%s]; None of the given processors supports it.', $id));
                continue;
            }

            $logger->info(sprintf('%sFinished processing source[%s]', $depthStr, $id));
        }

        if ($root) {
            $io->endOverwrite();
        }
    }

    /**
     * Verifies list definition array and returns the processed array.
     *
     * @param array $data List data
     *
     * @return array Processed list definition array
     */
    protected function verify($data)
    {
        return (new SymfonyConfig\Definition\Processor())->processConfiguration(
            new EntryListDefinition(),
            [$data]
        );
    }

    /**
     * Gets the path to a category within the category tree if defined.
     *
     * @param $category
     * @param $tree
     * @param int $depth
     *
     * @return array|bool
     */
    protected function getCategoryPath($category, array $tree = null, $depth = 0)
    {
        if ($tree === null) {
            $tree = $this->data['options']['categoryTree'];
        }

        $path = [];
        foreach ($tree as $parent => $child) {
            if ($depth === 0) {
                $path = [];
            }

            if (is_array($child)) {
                $path[] = $parent;
            } elseif ($category === $child) {
                $path[] = $category;
            }

            if ($category === $parent) {
                return $path;
            }

            if (is_array($child)) {
                if (in_array($category, $child)) {
                    $path[] = $category;

                    return $path;
                }

                $path = array_merge($path, $this->getCategoryPath($category, $child, $depth + 1));
            }

            if (in_array($category, $path)) {
                return $path;
            }
        }

        if ($depth === 0 && !in_array($category, $path)) {
            return false;
        }

        return $path;
    }
}
