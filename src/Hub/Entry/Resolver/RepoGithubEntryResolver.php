<?php

namespace Hub\Entry\Resolver;

use Github\Utils\RepoInspector\GithubRepoInspectorInterface;
use Github\Utils\RepoInspector\Exception\RepoInspectorException;
use Hub\Exceptions\EntryResolveFailedException;
use Hub\Filesystem\Filesystem;
use Hub\Entry\EntryInterface;
use Hub\Entry\RepoGithubEntryInterface;
use Hub\Workspace\WorkspaceInterface;

/**
 * Resolver for github repos.
 */
class RepoGithubEntryResolver implements EntryResolverInterface
{
    /**
     * @var GithubRepoInspectorInterface
     */
    protected $inspector;

    /**
     * @var WorkspaceInterface
     */
    protected $workspace;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Constructor.
     *
     * @param GithubRepoInspectorInterface $inspector
     * @param WorkspaceInterface           $workspace
     * @param Filesystem                   $filesystem
     */
    public function __construct(GithubRepoInspectorInterface $inspector, Filesystem $filesystem, WorkspaceInterface $workspace)
    {
        $this->inspector = $inspector;
        $this->workspace = $workspace;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     *
     * @param RepoGithubEntryInterface $entry
     */
    public function resolve(EntryInterface $entry, $force = false)
    {
        $cached = $this->read($entry);
        if($cached instanceof RepoGithubEntryInterface && !$force){
            $entry->set($cached->get());
            return;
        }

        $author = $entry->getAuthor();
        $name = $entry->getName();

        try {
            $repo = $this->inspector->inspect($author, $name);
        } catch (RepoInspectorException $e) {
            throw new EntryResolveFailedException(sprintf('Github Repo Inspector failed; %s', $e->getMessage()), 0, $e);
        }

        $entry->merge([
            'desc' => $this->cleanStr($repo['description']),
            'language' => $repo['language'],
            'score' => $repo['scores_avg'],
            'scores' => $repo['scores'],
            'pushed' => date(\DateTime::ISO8601, strtotime($repo['pushed_at'])),
        ]);

        try {
            $this->save($entry);
        } catch (\Exception $e) {
            throw new EntryResolveFailedException(sprintf('Failed caching Github repo; %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param RepoGithubEntryInterface $entry
     */
    public function supports(EntryInterface $entry)
    {
        return $entry instanceof RepoGithubEntryInterface;
    }

    /**
     * {@inheritdoc}
     *
     * @param RepoGithubEntryInterface $entry
     */
    public function isResolved(EntryInterface $entry)
    {
        if (!$this->supports($entry)) {
            throw new \UnexpectedValueException("Shouldn't receive an unsupported entry");
        }

        $cached = $this->read($entry);
        return $cached instanceof RepoGithubEntryInterface;
    }

    /**
     * Fetches the cached entry.
     *
     * @param RepoGithubEntryInterface $entry
     *
     * @return RepoGithubEntryInterface|bool
     */
    protected function read(RepoGithubEntryInterface $entry)
    {
        $path = $this->getPath($entry);
        if (!file_exists($path)) {
            return false;
        }

        return unserialize(file_get_contents($path));
    }

    /**
     * Saves an entry to file.
     *
     * @param RepoGithubEntryInterface $entry
     *
     * @return int
     */
    protected function save(RepoGithubEntryInterface $entry)
    {
        $path = $this->getPath($entry);

        return $this->filesystem->write($path, serialize($entry));
    }

    /**
     * Gets the path of the entry cahce file.
     *
     * @param RepoGithubEntryInterface $entry
     *
     * @return string
     */
    protected function getPath(RepoGithubEntryInterface $entry)
    {
        return $this->workspace->path(['cache/entries', $entry->getType(), $entry->getAuthor(), $entry->getName()]);
    }

    /**
     * Remove all non printable characters in a string.
     *
     * @param $string
     *
     * @return mixed
     */
    protected function cleanStr($string)
    {
        return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $string));
    }
}
