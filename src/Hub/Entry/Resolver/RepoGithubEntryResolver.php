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
        $this->inspector  = $inspector;
        $this->workspace  = $workspace;
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
        if ($cached instanceof RepoGithubEntryInterface && !$force) {
            // Only merge the fields that we provide
            $fields = ['description', 'language', 'scores_avg', 'scores', 'pushed'];
            foreach ($fields as $field) {
                $entry->set($field, $cached->get($field));
            }

            return;
        }

        $author = $entry->getAuthor();
        $name   = $entry->getName();

        try {
            $repo = $this->inspector->inspect($author, $name);
        } catch (RepoInspectorException $e) {
            throw new EntryResolveFailedException(sprintf('Github Repo Inspector failed; %s', $e->getMessage()), 0, $e);
        }

        $entry->merge([
            'description' => $this->cleanStr($repo['description']),
            'language'    => $repo['language'],
            'scores_avg'  => $repo['scores_avg'],
            'scores'      => $repo['scores'],
            'pushed'      => strtotime($repo['pushed_at']),
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
    public function isCached(EntryInterface $entry)
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
        $author = $entry->getAuthor();

        return $this->workspace->path(['cache/entries', $entry->getType(), $author[0], $author[1], $author, $entry->getName()]);
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
        // Strip non-utf chars
        $string = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $string);
        // Strip github emoticons
        return trim(preg_replace('/\:[^\:]+\:/', '', $string));
    }
}
