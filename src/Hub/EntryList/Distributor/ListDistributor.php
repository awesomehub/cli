<?php

declare(strict_types=1);

namespace Hub\EntryList\Distributor;

use Hub\Build\BuildInterface;
use Hub\Entry\RepoGithubEntry;
use Hub\Entry\RepoGithubEntryInterface;
use Hub\EntryList\EntryListInterface;

/**
 * Distributes lists into API consumable files.
 */
class ListDistributor implements ListDistributorInterface
{
    private const RANK_BANDS = [1, 3, 5, 10, 50, 90];

    protected ?EntryListInterface $list;

    /** @var array<string, mixed> */
    protected ?array $listCache;
    protected ?string $listUrl;
    protected ?int $listUpdatedAt;

    /** @var array<string, array<string, array<string, mixed>>> */
    protected array $listCollections = [];
    protected array $config = [
        'collections' => [],
    ];

    public function __construct(protected BuildInterface $build, protected ?BuildInterface $cachedBuild = null, ?array $config = null)
    {
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }
    }

    public function distribute(EntryListInterface $list): void
    {
        if (!$list->isResolved()) {
            throw new \LogicException('Cannot distribute a list that is not yet resolved');
        }

        $this->list = $list;
        $this->listUrl = null;
        $this->listCache = [];
        $this->listUpdatedAt = null;

        $this->loadListCache();
        $this->buildList();
        $this->saveListCache();

        // All lists goes this collection
        $this->addToCollection('all');

        foreach ($this->config['collections'] as $collection => $lists) {
            if (!\is_array($lists)) {
                throw new \UnexpectedValueException(\sprintf('Expected array of list names but got %s', \gettype($lists)));
            }

            foreach ($lists as $listId) {
                if (strtolower((string) $listId) === $this->list->getId()) {
                    $this->addToCollection($collection);
                }
            }
        }
    }

    public function finalize(): void
    {
        if (empty($this->listCollections)) {
            $this->build->set('urls', new \stdClass());

            return;
        }

        $urls = [];
        foreach ($this->listCollections as $collectionId => $listsById) {
            $lists = array_values($listsById);
            $entriesCount = 0;
            foreach ($lists as $item) {
                $entriesCount += $item['entries'] ?? 0;
            }

            $collectionPayload = [
                'id' => $collectionId,
                'lists' => $lists,
                'entries' => $entriesCount,
            ];

            $relativePath = \sprintf('collection/%s', $collectionId);
            $buildPath = $this->build->write($relativePath, $collectionPayload);
            $urls[$collectionId] = $buildPath;
        }

        ksort($urls);
        $this->build->set('urls', $urls);
    }

    /**
     * Builds the current list.
     */
    protected function buildList(): void
    {
        $updated = false;
        $entries = [];
        $entriesTotalScore = 0;
        $entriesCount = 0;
        foreach ($this->list->getEntries() as $entry) {
            $entryData = $entry->get();
            $entryData['updated'] = time();
            $entryDataCache = $this->listCache[$entry->getId()] ?? $entryData;
            $diff = $this->deepDiffArray($entryData, $entryDataCache);
            if (1 === \count($diff)) {
                $entryData['updated'] = $entryDataCache['updated'];
            } else {
                $updated = true;
            }
            $this->listCache[$entry->getId()] = $entryData;
            if ($entry instanceof RepoGithubEntryInterface) {
                // Ignore archived entries and entries with score < 50
                if ($entryData['scores_avg'] < 50 || $entryData['archived']) {
                    $this->list->removeEntry($entry);

                    continue;
                }
                $entriesTotalScore += $entryData['scores_avg'];
                $entryData = $this->buildEntryRepoGithub($entryData, $entryDataCache);
            }

            $entryType = $entry::getType();
            $entries[$entryType][] = $entryData;
            ++$entriesCount;
        }

        $githubRepoType = RepoGithubEntry::getType();
        if (!empty($entries[$githubRepoType])) {
            usort($entries[$githubRepoType], static function (array $a, array $b): int {
                $scoreA = (float) ($a['score'] ?? 0);
                $scoreB = (float) ($b['score'] ?? 0);

                if ($scoreA === $scoreB) {
                    $idA = strtolower($a['author'].'/'.$a['name']);
                    $idB = strtolower($b['author'].'/'.$b['name']);

                    return $idA <=> $idB;
                }

                return $scoreB <=> $scoreA;
            });

            $totalGithubEntries = \count($entries[$githubRepoType]);
            $bands = [];
            foreach (self::RANK_BANDS as $bandPercentile) {
                $bands[$bandPercentile] = (int) max(1, ceil($totalGithubEntries * ($bandPercentile / 100)));
            }

            $rank = 0;
            $position = 0;
            $previousScore = null;
            foreach ($entries[$githubRepoType] as $index => $entry) {
                ++$position;
                $score = (float) ($entry['score'] ?? 0);
                if (null === $previousScore || $score !== $previousScore) {
                    $rank = $position;
                    $previousScore = $score;
                }

                $band = 100;
                foreach ($bands as $bandValue => $threshold) {
                    if ($rank <= $threshold) {
                        $band = $bandValue;

                        break;
                    }
                }
                $entries[$githubRepoType][$index]['rank'] = $band;
            }
        }

        $this->list->set('score', (int) ($entriesTotalScore / max(1, $entriesCount)));

        $list = [
            'id' => $this->list->getId(),
            'name' => $this->list->get('name'),
            'copyright' => $this->list->get('copyright'),
            'desc' => $this->list->get('desc'),
            'score' => $this->list->get('score'),
            'cats' => array_values($this->list->getCategories()),
            'updated' => time(),
        ];

        $cid = \sprintf('list:%s', $list['id']);
        $listCache = $this->listCache[$cid] ?? $list;
        $diff = $this->deepDiffArray($list, $listCache);
        if (!$updated && 1 === \count($diff)) {
            $list['updated'] = $listCache['updated'];
        } else {
            $this->listCache[$cid] = $list;
        }

        $list['entries'] = $entries;
        $relativePath = \sprintf('list/%s', $list['id']);
        $buildPath = $this->build->write($relativePath, $list);

        $this->listUrl = $buildPath;
        $this->listUpdatedAt = $list['updated'];
    }

    /**
     * Builds an API consumable output of a RepoGithubEntry.
     */
    protected function buildEntryRepoGithub(array $current, array $cached): array
    {
        return [
            'author' => $current['author'],
            'name' => $current['name'],
            'desc' => $current['description'],
            'lang' => $current['language'],
            'lic' => $current['license'],
            'cats' => $current['categories'],
            'score' => $current['scores_avg'],
            'scores' => $current['scores'],
            'tags' => array_map('strtolower', $current['topics']),
            'hglt' => $current['highlight']['message'],
        ];
    }

    /**
     * Adds the current list to a collection.
     */
    protected function addToCollection(string $id): void
    {
        if (null === $this->listUrl) {
            throw new \RuntimeException('List URL is not available for the current distribution.');
        }

        if (!isset($this->listCollections[$id])) {
            $this->listCollections[$id] = [];
        }

        $this->listCollections[$id][$this->list->getId()] = [
            'id' => $this->list->getId(),
            'name' => $this->list->get('name'),
            'desc' => $this->list->get('desc'),
            'score' => $this->list->get('score'),
            'entries' => \count($this->list->getEntries()),
            'updated' => $this->listUpdatedAt,
            'url' => $this->listUrl,
        ];
    }

    protected function loadListCache(): void
    {
        if (null === $this->cachedBuild) {
            return;
        }

        $file = $this->getListCachePath();
        if (!$this->cachedBuild->exists($file, true)) {
            return;
        }

        $data = $this->cachedBuild->read($file, true);
        $this->listCache = @unserialize($data);
    }

    protected function saveListCache(): void
    {
        $this->build->write(
            $this->getListCachePath(),
            serialize($this->listCache),
            true,
            false
        );
    }

    protected function getListCachePath(): string
    {
        return \sprintf('objects/%s.cache', $this->list->getId());
    }

    /**
     * Recursively computes the difference of arrays with additional index check.
     *
     * This is a version of array_diff_assoc() that supports multidimensional
     * arrays.
     *
     * @param array $source The array to compare from
     * @param array $dest   The array to compare to
     *
     * @return array Returns an array containing all the values from array1 that are not present in array2
     */
    protected function deepDiffArray(array $source, array $dest): array
    {
        $difference = [];
        foreach ($source as $key => $value) {
            $keyDiff = !\array_key_exists($key, $dest);
            if (\is_array($value)) {
                if ($keyDiff || !\is_array($dest[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->deepDiffArray($value, $dest[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif ($keyDiff || $dest[$key] !== $value) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }
}
