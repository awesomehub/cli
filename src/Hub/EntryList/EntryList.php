<?php

declare(strict_types=1);

namespace Hub\EntryList;

use Hub\Entry\EntryInterface;
use Hub\Entry\Resolver\AsyncResolverInterface;
use Hub\Entry\Resolver\EntryResolverInterface;
use Hub\EntryList\Source\SourceInterface;
use Hub\EntryList\SourceProcessor\SourceProcessorInterface;
use Hub\Exceptions\EntryResolveFailedException;
use Hub\IO\IOInterface;
use Hub\Util\NestedArray;
use Spatie\Async\Pool;
use Symfony\Component\Config\Definition as ConfigDefinition;

/**
 * The Base List class.
 */
class EntryList implements EntryListInterface
{
    protected array $data;

    /** @var EntryInterface[] */
    protected array $entries = [];
    protected array $categories = [];
    protected int $categoryLastInsert = 0;
    protected bool $processed = false;
    protected bool $resolved = false;
    protected array $debug = [];
    protected int $concurrentStatusLines = 0;
    protected int $concurrentSpinnerIndex = 0;
    protected array $concurrentSpinnerFrames = ['-', '\\', '|', '/'];

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
        } catch (ConfigDefinition\Exception\Exception $e) {
            throw new \InvalidArgumentException("Unable to process the list definition file; {$e->getMessage()}", 0, $e);
        }

        foreach ($this->data['sources'] as $i => $source) {
            $options = isset($this->data['options']['source'])
                // Merge top-level source options
                ? NestedArray::mergeDeep($this->data['options']['source'], $source['options'])
                : $source['options'];
            $this->data['sources'][$i] = new Source\Source($source['type'], $source['data'], $options);
        }
    }

    public function getId(): string
    {
        return strtolower($this->get('id'));
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getEntries(): array
    {
        return $this->entries;
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function has($key): bool
    {
        return \array_key_exists($key, $this->data);
    }

    public function get(?string $key = null): mixed
    {
        if (0 === \func_num_args()) {
            return $this->data;
        }

        if (!\array_key_exists($key, $this->data)) {
            throw new \InvalidArgumentException(\sprintf("Trying to get an undefined list data key '%s'", $key));
        }

        return $this->data[$key];
    }

    public function set(array|string $key, mixed $value = null): void
    {
        if (1 === \func_num_args()) {
            if (!\is_array($key)) {
                throw new \UnexpectedValueException(\sprintf('Expected array but got %s', var_export($key, true)));
            }

            $this->data = $key;

            return;
        }

        $this->data[$key] = $value;
    }

    public function process(IOInterface $io, array $processors): void
    {
        if (empty($processors)) {
            throw new \LogicException('Cannot process the list; No source processors has been provided');
        }
        $logger = $io->getLogger();

        $logger->info('Processing list sources');
        $this->processSources($io, $processors);
        $this->processed = true;
        $logger->info(\sprintf('Processed %d entry(s)', \count($this->entries)));
    }

    public function resolve(IOInterface $io, array $resolvers, bool $force = false, ?int $concurrency = null): void
    {
        // Sanity check
        if (!$this->isProcessed()) {
            throw new \LogicException('Can not resolve the list while it is not processed');
        }

        if (empty($resolvers)) {
            throw new \LogicException('Cannot resolve the list; No resolvers has been provided');
        }

        if (empty($this->entries)) {
            throw new \LogicException('No entries to resolve');
        }

        $logger = $io->getLogger();
        $logger->info('Resolving list entries');

        $concurrency = max(1, $concurrency);
        $canResolveConcurrently = $concurrency > 1 && $this->resolversSupportAsync($resolvers);
        if ($concurrency > 1 && !$canResolveConcurrently) {
            $logger->debug('Parallel resolve disabled; at least one resolver does not support async execution.');
        }

        if ($canResolveConcurrently) {
            [$i, $ir, $ic] = $this->resolveConcurrently($io, $resolvers, $force, $concurrency);
        } else {
            $io->startOverwrite();
            $useOverwrite = $io->isOverwritable();

            [$i, $ir, $ic] = $this->resolveSerial($io, $resolvers, $force);

            if ($useOverwrite) {
                $io->endOverwrite();
            }
        }

        $this->resolved = true;
        $logger->info(\sprintf(
            'Resolved %d/%d entry(s) with %d cached entry(s)',
            $ir,
            $i,
            $ic
        ));
    }

    public function finalize(IOInterface $io): void
    {
        $logger = $io->getLogger();

        $logger->info('Merging entry aliases');
        $im = 0;
        foreach ($this->entries as $entry) {
            $aliases = $entry->getAliases();
            foreach ($aliases as $aliasId) {
                if (isset($this->entries[$aliasId])) {
                    $entry->merge($this->entries[$aliasId]->get());
                    $this->removeEntry($this->entries[$aliasId]);
                    ++$im;
                }
            }
        }
        $logger->info(\sprintf('Merged %d entry(s) with aliases', $im));

        $logger->info('Organizing categories');
        foreach ($this->entries as $entry) {
            $categories = [];
            foreach ($entry->get('categories') as $category) {
                // Strip non-utf chars
                $category = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $category);
                $category = trim($category);
                if (!empty($category) && !\in_array($category, $categories, true)) {
                    $categories[] = $category;
                }
            }

            $categoryIds = [];
            foreach ($categories as $category) {
                $categoryIds = array_merge(
                    $categoryIds,
                    $this->insertCategory($category, [
                        'all' => 1,
                        $entry::getType() => 1,
                    ])
                );
            }

            $entry->set('categories', $categoryIds);
        }

        usort($this->categories, static fn ($x, $y): int => strcmp($x['path'], $y['path']));

        // Add category order
        $categoryOrder = $this->data['options']['categoryOrder'];
        foreach ($this->categories as $i => $category) {
            $order = 20;
            if (\in_array($category['path'], $categoryOrder, true)) {
                $order = (int) array_search($category['path'], $categoryOrder, true);
            }
            $this->categories[$i]['order'] = $order;
        }

        $logger->info(\sprintf('Organized %d category(s)', \count($this->categories)));
    }

    public function removeEntry(EntryInterface $entry): void
    {
        // Sanity check
        if (!$this->isProcessed()) {
            throw new \LogicException('Can not remove an entry while the list is not processed');
        }

        // Remove from entries
        unset($this->entries[$entry->getId()]);

        $entryCategories = $entry->get('categories');
        if ([] === $this->categories || [] === $entryCategories) {
            return;
        }

        // Update cat counts
        foreach ($this->categories as $i => $category) {
            if (\in_array($category['id'], $entryCategories, true)) {
                --$this->categories[$i]['count']['all'];
                --$this->categories[$i]['count'][$entry->getType()];

                // Remove the category if it hs no entries
                if (1 > $this->categories[$i]['count']['all']) {
                    unset($this->categories[$i]);
                }
            }
        }
    }

    /**
     * @param EntryResolverInterface[] $resolvers
     *
     * @return array{int,int,int}
     */
    protected function resolveSerial(IOInterface $io, array $resolvers, bool $force): array
    {
        $logger = $io->getLogger();
        $indicator = ' [ %%spinner%% ] Resolving entry#%d => %s (completed %d/%d)';
        $totalEntries = \count($this->entries);
        $i = $ir = $ic = 0;
        foreach ($this->entries as $id => $entry) {
            ++$i;
            $resolvedWith = false;
            $isCached = false;

            foreach ($resolvers as $resolver) {
                if (!$resolver->supports($entry)) {
                    continue;
                }

                $resolvedWith = $resolver;
                $isCached = $resolver->isCached($entry);
                $io->write(\sprintf($indicator, $i, $id, $ir, $totalEntries));

                try {
                    $resolver->resolve($entry, $force);
                } catch (EntryResolveFailedException $e) {
                    $this->removeEntry($entry);
                    $logger->warning(\sprintf(
                        "Failed resolving entry#%d [%s] with '%s'; %s",
                        $i,
                        $id,
                        $resolver::class,
                        $e->getMessage()
                    ));

                    continue 2;
                }
            }

            // Check if no resolver can resolve this entry
            if (false === $resolvedWith) {
                $this->removeEntry($entry);
                $logger->warning(\sprintf(
                    "Ignoring entry#%d [%s] of type '%s'; None of the given resolvers supports it",
                    $i,
                    $id,
                    $entry::class
                ));

                continue;
            }

            if ($isCached) {
                ++$ic;
            }

            ++$ir;
        }

        return [$i, $ir, $ic];
    }

    /**
     * @param EntryResolverInterface[] $resolvers
     *
     * @return array{int,int,int}
     */
    protected function resolveConcurrently(IOInterface $io, array $resolvers, bool $force, int $concurrency): array
    {
        $logger = $io->getLogger();
        $pool = Pool::create()->concurrency($concurrency);
        $this->concurrentStatusLines = 0;
        $this->concurrentSpinnerIndex = 0;

        $i = 0;
        $resolved = 0;
        $cached = 0;
        $meta = [];
        $active = [];
        $pending = [];
        $total = \count($this->entries);

        $logger->info(\sprintf('Resolving %d entry(s) using %d worker(s)', $total, $concurrency));

        $decorated = $io->isDecorated();

        foreach ($this->entries as $id => $entry) {
            ++$i;

            $resolverData = $this->prepareAsyncResolver($resolvers, $entry);
            if (null === $resolverData) {
                $this->removeEntry($entry);
                $logger->warning(\sprintf(
                    "Ignoring entry#%d [%s] of type '%s'; None of the given resolvers supports it",
                    $i,
                    $id,
                    $entry::class
                ));

                continue;
            }

            $meta[$id] = [
                'index' => $i,
                'resolver' => $resolverData['class'],
                'cached' => $resolverData['cached'],
                'track' => true,
                'tracked' => false,
            ];

            $completed = $resolved;

            if ($decorated) {
                if (\count($active) < $concurrency) {
                    $active[$id] = \sprintf('entry#%d/%d %s', $i, $total, $id);
                    $meta[$id]['tracked'] = true;
                    $this->renderConcurrentStatus($io, $active, $total, $completed);
                } else {
                    $pending[] = $id;
                }
            } else {
                $io->writeln(\sprintf(
                    'Resolving entry#%d/%d => %s (completed %d/%d)',
                    $i,
                    $total,
                    $id,
                    $completed,
                    $total
                ));
            }

            $pool
                ->add(function () use ($resolverData, $entry, $force) {
                    /** @var AsyncResolverInterface $resolverClass */
                    $resolverClass = $resolverData['class'];
                    $resolver = $resolverClass::createAsyncResolver($resolverData['context']);
                    $resolver->resolve($entry, $force);

                    return $entry;
                })
                ->then(function (EntryInterface $updatedEntry) use ($id, &$resolved, &$cached, &$meta, &$active, &$pending, $decorated, $io, $total): void {
                    $this->entries[$id] = $updatedEntry;
                    ++$resolved;

                    if (!empty($meta[$id]['cached'])) {
                        ++$cached;
                    }

                    if ($decorated && !empty($meta[$id]['track']) && !empty($meta[$id]['tracked'])) {
                        unset($active[$id]);

                        while (!empty($pending)) {
                            $nextId = array_shift($pending);
                            if (empty($meta[$nextId]['track'])) {
                                continue;
                            }

                            $meta[$nextId]['tracked'] = true;
                            $active[$nextId] = \sprintf('entry#%d/%d %s', $meta[$nextId]['index'], $total, $nextId);

                            break;
                        }
                    }
                    $this->renderConcurrentStatus($io, $active, $total, $resolved);
                    unset($meta[$id]);
                })
                ->catch(function (\Throwable $throwable) use ($id, &$resolved, &$cached, &$meta, $logger, &$active, &$pending, $decorated, $io, $total): void {
                    if (isset($this->entries[$id])) {
                        $this->removeEntry($this->entries[$id]);
                    }

                    $details = $meta[$id] ?? ['index' => '?', 'resolver' => 'unknown'];
                    unset($active[$id]);
                    if ($decorated && !empty($meta[$id]['track']) && !empty($meta[$id]['tracked'])) {
                        while (!empty($pending)) {
                            $nextId = array_shift($pending);
                            if (empty($meta[$nextId]['track'])) {
                                continue;
                            }

                            $meta[$nextId]['tracked'] = true;
                            $active[$nextId] = \sprintf('entry#%d/%d %s', $meta[$nextId]['index'], $total, $nextId);

                            break;
                        }
                    }

                    $this->clearConcurrentStatus($io);
                    $message = $throwable->getMessage();
                    if (str_contains($message, "\n")) {
                        $message = strtok($message, "\r\n");
                    }
                    $logger->warning(\sprintf(
                        "Failed resolving entry#%s [%s] with '%s'; %s",
                        $details['index'],
                        $id,
                        $details['resolver'],
                        $message
                    ));

                    if ($decorated) {
                        $this->renderConcurrentStatus($io, $active, $total, $resolved);
                    }

                    unset($meta[$id]);
                })
            ;
        }

        $pool->wait();
        $this->clearConcurrentStatus($io);

        return [$i, $resolved, $cached];
    }

    /**
     * @param EntryResolverInterface[] $resolvers
     */
    protected function resolversSupportAsync(array $resolvers): bool
    {
        foreach ($resolvers as $resolver) {
            if (!$resolver instanceof AsyncResolverInterface) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param EntryResolverInterface[] $resolvers
     *
     * @return null|array{class: class-string<AsyncResolverInterface>, cached: bool, context: array<string, mixed>}
     */
    protected function prepareAsyncResolver(array $resolvers, EntryInterface $entry): ?array
    {
        foreach ($resolvers as $resolver) {
            if (!$resolver->supports($entry)) {
                continue;
            }

            if (!$resolver instanceof AsyncResolverInterface) {
                throw new \LogicException(\sprintf(
                    "Resolver '%s' must implement %s to be used in async mode.",
                    $resolver::class,
                    AsyncResolverInterface::class
                ));
            }

            return [
                'class' => $resolver::class,
                'cached' => $resolver->isCached($entry),
                'context' => $resolver->getAsyncContext(),
            ];
        }

        return null;
    }

    protected function renderConcurrentStatus(IOInterface $io, array $active, int $total, int $completed): void
    {
        $output = $io->getOutput();
        $decorated = $output->isDecorated();

        if (!$decorated) {
            $io->writeln(\sprintf(
                'Resolving (%d active/%d total, %d completed): %s',
                \count($active),
                $total,
                $completed,
                implode(', ', array_values($active))
            ));
            return;
        }

        $this->clearConcurrentStatus($io);
        $lines = $this->formatConcurrentStatusLines($active, $total, $completed);
        $output->writeln($lines);
        $this->concurrentStatusLines = \count($lines);
    }

    protected function formatConcurrentStatusLines(array $active, int $total, int $completed): array
    {
        $spinner = $this->concurrentSpinnerFrames[$this->concurrentSpinnerIndex % \count($this->concurrentSpinnerFrames)];
        ++$this->concurrentSpinnerIndex;

        $lines = [
            \sprintf(
                ' [ %s ] Resolving %d in-flight entries | %d/%d completed',
                $spinner,
                \count($active),
                $completed,
                $total
            ),
        ];

        foreach ($active as $entry) {
            $lines[] = '   • '.$entry;
        }

        return $lines;
    }

    protected function clearConcurrentStatus(IOInterface $io): void
    {
        $output = $io->getOutput();
        if (!$output->isDecorated()) {
            $io->writeln('');

            return;
        }

        if ($this->concurrentStatusLines > 0) {
            $output->write(str_repeat("\x1B[1A\x1B[2K", $this->concurrentStatusLines));
            $this->concurrentStatusLines = 0;
        }
    }

    /**
     * Adds an entry to the list.
     */
    protected function addEntry(EntryInterface $entry, SourceInterface $source): void
    {
        // Sanity check
        if ($this->isProcessed() || $this->isResolved()) {
            throw new \LogicException('Can not add new entries after the list has been processed');
        }

        $id = $entry->getId();
        if ($source->hasOption('exclude')) {
            foreach ($source->getOption('exclude', []) as $regex) {
                $regex = '/' !== $regex[0] ? "/{$regex}/" : $regex;
                if (false === @preg_match($regex, '')) {
                    throw new \InvalidArgumentException(\sprintf("Invalid exclude regex '%s'", $regex));
                }

                if (preg_match($regex, $id)) {
                    return;
                }
            }
        }

        // Check if an already existing entry exists
        if (isset($this->entries[$id])) {
            $this->entries[$id]->merge($entry->get());
            $entry = $this->entries[$id];
        }

        // Check if a single category is defined
        if ($source->hasOption('category')) {
            $entry->set('categories', [
                $source->getOption('category', null),
            ]);
            // Save the entry
            $this->entries[$id] = $entry;

            return;
        }

        $categories = [];
        if ($entry->has('categories')) {
            $categories = $entry->get('categories');
        }

        // Add source categories
        if ($source->hasOption('categories')) {
            foreach ($source->getOption('categories', []) as $category => $regexs) {
                if (!\is_array($regexs)) {
                    $regexs = [$regexs];
                }

                foreach ($regexs as $regex) {
                    if ($negative = ('!' === $regex[0])) {
                        $regex = substr($regex, 1);
                    }

                    $regex = '*' === $regex ? '.*' : $regex;
                    $regex = '/' !== $regex[0] ? "/{$regex}/" : $regex;
                    if (false === @preg_match($regex, '')) {
                        throw new \InvalidArgumentException(\sprintf("Invalid category regex '%s'", $regex));
                    }

                    $assert = preg_match($regex, $id);
                    if (($negative && !$assert) || (!$negative && $assert)) {
                        if (!\is_array($category)) {
                            $category = [$category];
                        }
                        $categories = array_merge($categories, $category);
                    }
                }
            }
        }

        // Set the final categories
        $entry->set('categories', $categories);
        // Save the entry
        $this->entries[$id] = $entry;
    }

    /**
     * Recursively processes list sources.
     *
     * @param SourceProcessorInterface[] $processors
     * @param null|SourceInterface[]     $sources
     */
    protected function processSources(IOInterface $io, array $processors, array $sources = [], int $depth = 0): void
    {
        $root = 0 === $depth;
        $logger = $io->getLogger();
        $depthStr = $root ? '' : str_repeat('|_ ', $depth);
        $indicator = ' [ %%spinner%% ] %s (%%elapsed%%)';

        if ($root) {
            $io->startOverwrite();
            $sources = $this->data['sources'];
        }

        foreach ($sources as $index => $source) {
            $id = ($root ? 'index='.$index.' ' : '').'type='.$source->getType();
            $processedWith = false;
            $callback = function ($event, $payload) use ($source, $io, $indicator): void {
                switch ($event) {
                    case SourceProcessorInterface::ON_STATUS_UPDATE:
                        if ('error' === $payload['type']) {
                            $io->getLogger()->warning($payload['message']);

                            break;
                        }
                        $io->write(\sprintf($indicator, $payload['message']));

                        break;

                    case SourceProcessorInterface::ON_ENTRY_CREATED:
                        $this->addEntry($payload, $source);

                        break;

                    default:
                        throw new \UnexpectedValueException(\sprintf("Unsupported source processor event '%s'", $event));
                }
            };

            foreach ($processors as $processor) {
                $processorName = basename(str_replace('\\', '/', $processor::class));

                switch ($processor->getAction($source)) {
                    case SourceProcessorInterface::ACTION_PARTIAL_PROCESSING:
                        $processedWith = $processor;
                        $logger->info(\sprintf("%sProcessing source[%s] with '%s'", $depthStr, $id, $processorName));

                        try {
                            $childSources = $processor->process($source, $callback);
                        } catch (\Exception $e) {
                            $logger->critical(\sprintf(
                                "Failed processing source[%s] with '%s'; %s",
                                $id,
                                $processorName,
                                $e->getMessage()
                            ));

                            break;
                        }

                        if (!\is_array($childSources)) {
                            $childSources = [$childSources];
                        }

                        if ([] === $childSources) {
                            $logger->warning(\sprintf(
                                "No child sources from processing source[%s] with '%s'",
                                $id,
                                $processorName
                            ));

                            break;
                        }

                        $this->processSources($io, $processors, $childSources, $depth + 1);

                        break;

                    case SourceProcessorInterface::ACTION_PROCESSING:
                        $processedWith = $processor;
                        $logger->info(\sprintf("%sProcessing source[%s] with '%s'", $depthStr, $id, $processorName));

                        try {
                            $processor->process($source, $callback);
                        } catch (\Exception $e) {
                            $logger->critical(\sprintf(
                                "Failed processing source[%s] with '%s'; %s",
                                $id,
                                $processorName,
                                $e->getMessage()
                            ));
                        }

                        break;

                    case SourceProcessorInterface::ACTION_SKIP:
                        break;

                    default:
                        throw new \UnexpectedValueException(\sprintf("Got an invalid processing mode from processor '%s'", $processor::class));
                }
            }

            // Check if no processor can process this source
            if (false === $processedWith) {
                $logger->critical(\sprintf('Ignoring source[%s]; None of the given processors supports it', $id));

                continue;
            }

            $logger->info(\sprintf('%sFinished processing source[%s]', $depthStr, $id));
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
    protected function verify(array $data): array
    {
        return (new ConfigDefinition\Processor())->processConfiguration(
            new EntryListDefinition(),
            [$data]
        );
    }

    /**
     * Adds a new category.
     *  Takes a category path like 'Category/Sub Category/Demo', insert it into the
     *   current list categories array then returns the created categories ids as array.
     */
    protected function insertCategory(string $category, array $count = []): array
    {
        $path = [];
        $return = [];
        foreach (explode('/', trim($category, '/')) as $title) {
            $parentPath = implode('/', $path);
            $title = ucfirst(trim($title));
            $path[] = $this->slugify($title);
            $pathString = implode('/', $path);
            $paths = array_column($this->categories, 'path', 'id');
            if (\in_array($pathString, $paths, true)) {
                $return[] = $id = array_search($pathString, $paths, true);
                $indexes = array_column($this->categories, 'id');
                $object = &$this->categories[array_search($id, $indexes, true)];
                // Allow overwriting the category title
                $object['title'] = $title;
                foreach ($count as $ckey => $cval) {
                    if (!isset($object['count'][$ckey])) {
                        $object['count'][$ckey] = $cval;

                        continue;
                    }
                    $object['count'][$ckey] += $cval;
                }

                continue;
            }

            $return[] = $id = ++$this->categoryLastInsert;

            $this->categories[] = [
                'id' => $id,
                'title' => $title,
                'path' => $pathString,
                'parent' => (int) array_search($parentPath, $paths, true),
                'count' => $count,
            ];
        }

        return $return;
    }

    /**
     * Slugifies a given arbitrary string.
     */
    protected function slugify(string $str): string
    {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $str = preg_replace('/[^\w\-]/', ' ', $str);
        $str = str_replace(' ', '-', strtolower(trim($str)));
        $str = preg_replace('/-{2,}/', '-', $str);

        /*
         * @todo: fix this temporary workaround
         * We use 'search' route in the angular app for displaying list search results, therefore
         *  we can not have categories named 'search'
         */
        return 'search' === $str
            ? $str.'-2'
            : $str;
    }
}
