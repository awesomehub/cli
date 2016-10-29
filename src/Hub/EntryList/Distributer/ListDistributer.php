<?php

namespace Hub\EntryList\Distributer;

use Hub\Build\BuildInterface;
use Hub\Entry\EntryInterface;
use Hub\Entry\RepoGithubEntryInterface;
use Hub\EntryList\EntryListInterface;

/**
 * Distributes lists into API consumable files.
 */
class ListDistributer implements ListDistributerInterface
{
    /**
     * @var BuildInterface
     */
    protected $build;

    /**
     * @var BuildInterface
     */
    protected $cachedBuild;

    /**
     * @var EntryListInterface
     */
    protected $list;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $updated;

    /**
     * Constructor.
     *
     * @param BuildInterface      $build
     * @param BuildInterface|null $cached
     * @param array|null          $config
     */
    public function __construct(BuildInterface $build, BuildInterface $cached = null, array $config = null)
    {
        $this->build       = $build;
        $this->cachedBuild = $cached;
        $this->config      = [
            'collections' => [],
        ];

        if ($config) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function distribute(EntryListInterface $list)
    {
        if (!$list->isResolved()) {
            throw new \LogicException('Cannot distribute a list that is not yet resolved');
        }

        $this->list = $list;

        $this->buildList();

        // All lists goes this collection
        $this->addToCollection('all');

        foreach ($this->config['collections'] as $collection => $lists) {
            if (!is_array($lists)) {
                throw new \UnexpectedValueException(sprintf('Expected array of list names but got %s', gettype($lists)));
            }

            foreach ($lists as $list) {
                if (strtolower($list) == $this->list->getId()) {
                    $this->addToCollection($collection);
                }
            }
        }
    }

    /**
     * Builds the current list.
     */
    protected function buildList()
    {
        $updated = false;
        $entries = [];
        /** @var EntryInterface $entry */
        foreach ($this->list->get('entries') as $entry) {
            $entryData            = $entry->get();
            $entryData['updated'] = time();
            $entryDataCache       = $this->getCachedObject($entry->getId()) ?: $entryData;
            $diff                 = $this->deepDiffArray($entryData, $entryDataCache);
            if (count($diff) == 1) {
                $entryData['updated'] = $entryDataCache['updated'];
            } else {
                $updated = true;
                $this->setObject($entry->getId(), $entryData);
            }

            if ($entry instanceof RepoGithubEntryInterface) {
                // Ignore entries with very low score
                if($entryData['scores_avg'] < 10){
                    continue;
                }
                $entryData = $this->buildEntryRepoGithub($entryData, $entryDataCache);
            }

            $entries[$entry->getType()][] = $entryData;
        }

        $list = [
            'id'      => $this->list->getId(),
            'name'    => $this->list->get('name'),
            'desc'    => $this->list->get('desc'),
            'score'   => $this->list->get('score'),
            'cats'    => $this->list->get('categories'),
            'updated' => time(),
        ];

        $cid       = 'list:'.$list['id'];
        $listCache = $this->getCachedObject($cid) ?: $list;
        $diff      = $this->deepDiffArray($list, $listCache);
        if (count($diff) == 1 && !$updated) {
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
     *
     * @param array $current
     * @param array $cached
     *
     * @return array
     */
    protected function buildEntryRepoGithub(array $current, array $cached)
    {
        return [
            'author'   => $current['author'],
            'name'     => $current['name'],
            'desc'     => $current['description'],
            'lang'     => $current['language'],
            'cats'     => $current['categories'],
            'score'    => $current['scores_avg'],
            'scores'   => $current['scores'],
            'score_d'  => $current['scores_avg'] - $cached['scores_avg'],
            'scores_d' => [
                'p' => $current['scores']['p'] - $cached['scores']['p'],
                'h' => $current['scores']['h'] - $cached['scores']['h'],
                'a' => $current['scores']['a'] - $cached['scores']['a'],
                'm' => $current['scores']['m'] - $cached['scores']['m'],
            ],
            'pushed'  => $current['pushed'],
            'updated' => $current['updated'],
        ];
    }
    /**
     * Adds the current list to a collection.
     *
     * @param $collection
     */
    protected function addToCollection($collection)
    {
        $file       = 'lists/'.$collection;
        $collection = [
            'lists' => [],
        ];
        if ($this->build->exists($file)) {
            $collection['lists'] = $this->build->read($file);
        }

        $list = [
            'id'      => $this->list->getId(),
            'name'    => $this->list->get('name'),
            'desc'    => $this->list->get('desc'),
            'score'   => $this->list->get('score'),
            'entries' => count($this->list->get('entries')),
            'updated' => $this->updated,
        ];

        if (!in_array($list, $collection['lists'])) {
            array_push($collection['lists'], $list);
        }

        $this->build->write($file, $collection);
    }

    /**
     * Gets the value of a cached object.
     *
     * @param string $id
     *
     * @return mixed
     */
    protected function getCachedObject($id)
    {
        if (!$this->cachedBuild) {
            return false;
        }

        $idsha  = sha1($id);
        $cached = null;
        $file   = sprintf('objects/%s/%s/%s', $idsha[0], $idsha[1], $idsha);
        if (!$this->cachedBuild->exists($file, true)) {
            return false;
        }

        return unserialize($this->cachedBuild->read($file, true))
                ?: false;
    }

    /**
     * Writes an object data.
     *
     * @param string $id
     * @param mixed  $data
     */
    protected function setObject($id, $data)
    {
        $idsha  = sha1($id);
        $cached = null;
        $file   = sprintf('objects/%s/%s/%s', $idsha[0], $idsha[1], $idsha);
        $this->build->write($file, serialize($data), true);
    }

    /**
     * Recursively computes the difference of arrays with additional index check.
     *
     * This is a version of array_diff_assoc() that supports multidimensional
     * arrays.
     *
     * @param array $array1 The array to compare from
     * @param array $array2 The array to compare to
     *
     * @return array Returns an array containing all the values from array1 that are not present in array2
     */
    protected function deepDiffArray($array1, $array2)
    {
        $difference = [];
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!array_key_exists($key, $array2) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->deepDiffArray($value, $array2[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }
}
