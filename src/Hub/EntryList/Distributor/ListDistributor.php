<?php

declare(strict_types=1);

namespace Hub\EntryList\Distributor;

use Hub\Build\BuildInterface;
use Hub\Entry\RepoGithubEntryInterface;
use Hub\EntryList\EntryListInterface;

/**
 * Distributes lists into API consumable files.
 */
class ListDistributor implements ListDistributorInterface
{
    protected EntryListInterface $list;
    protected array $config;
    protected int $updated;

    public function __construct(protected BuildInterface $build, protected ?BuildInterface $cachedBuild = null, array $config = null)
    {
        $this->config = [
            'collections' => [],
        ];

        if ($config) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function distribute(EntryListInterface $list): void
    {
        if (!$list->isResolved()) {
            throw new \LogicException('Cannot distribute a list that is not yet resolved');
        }

        $this->list = $list;

        $this->buildList();

        // All lists goes this collection
        $this->addToCollection('all');

        foreach ($this->config['collections'] as $collection => $lists) {
            if (!\is_array($lists)) {
                throw new \UnexpectedValueException(sprintf('Expected array of list names but got %s', \gettype($lists)));
            }

            foreach ($lists as $listId) {
                if (strtolower($listId) === $this->list->getId()) {
                    $this->addToCollection($collection);
                }
            }
        }
    }

    /**
     * Builds the current list.
     */
    protected function buildList(): void
    {
        $updated = false;
        $entries = [];
        $entries_total_score = 0;
        $entries_count = 0;
        foreach ($this->list->getEntries() as $entry) {
            $entryData = $entry->get();
            $entryData['updated'] = time();
            $entryDataCache = $this->getCachedObject($entry->getId()) ?: $entryData;
            $diff = $this->deepDiffArray($entryData, $entryDataCache);
            if (1 === \count($diff)) {
                $entryData['updated'] = $entryDataCache['updated'];
            } else {
                $updated = true;
                $this->setObject($entry->getId(), $entryData);
            }

            if ($entry instanceof RepoGithubEntryInterface) {
                $entries_total_score += $entryData['scores_avg'];
                // Ignore archived entries and entries with no score
                if (0 === $entryData['scores_avg'] || $entryData['archived']) {
                    $this->list->removeEntry($entry);

                    continue;
                }
                $entryData = $this->buildEntryRepoGithub($entryData, $entryDataCache);
            }

            $entries[$entry::getType()][] = $entryData;
            ++$entries_count;
        }

        $this->list->set('score', (int) ($entries_total_score / $entries_count));

        $list = [
            'id' => $this->list->getId(),
            'name' => $this->list->get('name'),
            'desc' => $this->list->get('desc'),
            'score' => $this->list->get('score'),
            'cats' => array_values($this->list->getCategories()),
            'updated' => time(),
        ];

        $cid = 'list:'.$list['id'];
        $listCache = $this->getCachedObject($cid) ?: $list;
        $diff = $this->deepDiffArray($list, $listCache);
        if (!$updated && 1 === \count($diff)) {
            $list['updated'] = $listCache['updated'];
        } else {
            $this->setObject($cid, $list);
        }

        $list['entries'] = $entries;
        $this->build->write('list/'.$list['id'], $list);
        $this->updated = $list['updated'];
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
            'lic' => $current['licence'],
            'cats' => $current['categories'],
            'score' => $current['scores_avg'],
            'scores' => $current['scores'],
            'score_d' => $current['scores_avg'] - $cached['scores_avg'],
            'scores_d' => [
                'p' => $current['scores']['p'] - $cached['scores']['p'],
                'h' => $current['scores']['h'] - $cached['scores']['h'],
                'a' => $current['scores']['a'] - $cached['scores']['a'],
                'm' => $current['scores']['m'] - $cached['scores']['m'],
            ],
            'pushed' => $current['pushed'],
            'updated' => $current['updated'],
        ];
    }

    /**
     * Adds the current list to a collection.
     */
    protected function addToCollection(string $id): void
    {
        $file = 'lists/'.$id;
        $collection = [
            'lists' => [],
        ];
        if ($this->build->exists($file)) {
            $collection = $this->build->read($file);
        }

        $list = [
            'id' => $this->list->getId(),
            'name' => $this->list->get('name'),
            'desc' => $this->list->get('desc'),
            'score' => $this->list->get('score'),
            'entries' => \count($this->list->getEntries()),
            'updated' => $this->updated,
        ];

        if (!\in_array($list, $collection['lists'], true)) {
            $collection['lists'][] = $list;
            $collection['entries'] = ($collection['entries'] ?? 0) + $list['entries'];
        }

        $this->build->write($file, $collection);
    }

    /**
     * Gets the value of a cached object.
     */
    protected function getCachedObject(string $id): mixed
    {
        if (null === $this->cachedBuild) {
            return false;
        }

        $idsha = sha1($id);
        $file = sprintf('objects/%s/%s/%s', $idsha[0], $idsha[1], $idsha);
        if (!$this->cachedBuild->exists($file, true)) {
            return false;
        }

        return unserialize($this->cachedBuild->read($file, true))
                ?: false;
    }

    /**
     * Writes an object data.
     */
    protected function setObject(string $id, mixed $data): void
    {
        $idsha = sha1($id);
        $file = sprintf('objects/%s/%s/%s', $idsha[0], $idsha[1], $idsha);
        $this->build->write($file, serialize($data), true);
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
