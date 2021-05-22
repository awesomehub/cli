<?php

namespace Hub\Entry\Resolver;

use Github\Utils\RepoInspector\Exception\RepoInspectorException;
use Github\Utils\RepoInspector\GithubRepoInspectorInterface;
use Hub\Entry\EntryInterface;
use Hub\Entry\RepoGithubEntryInterface;
use Hub\Exceptions\EntryResolveFailedException;
use Hub\Filesystem\Filesystem;
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
        if ($cached && !$force) {
            // Only merge the fields that we provide
            $fields = ['author', 'name', 'description', 'language', 'licence', 'scores_avg', 'scores', 'pushed', 'archived'];
            foreach ($fields as $field) {
                $entry->set($field, $cached->get($field));
            }

            foreach ($cached->getAliases() as $alias) {
                $entry->addAlias($alias);
            }

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
            'description' => $this->cleanStr($repo['description']),
            'language' => $repo['language'],
            'licence' => $repo['licence_id'],
            'scores_avg' => $repo['scores_avg'],
            'scores' => $repo['scores'],
            'pushed' => strtotime($repo['pushed_at']),
            'archived' => $repo['archived'],
        ]);

        $fetchedAuthor = $repo['owner']['login'];
        $fetchedName = $repo['name'];
        if ($fetchedAuthor !== $author || $fetchedName !== $name) {
            $entry->merge([
                'author' => $fetchedAuthor,
                'name' => $fetchedName,
            ]);
            $entry->addAlias("repo.github:{$fetchedAuthor}/{$fetchedName}");
        }

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
        $cached = $this->read($entry);

        return $cached instanceof RepoGithubEntryInterface;
    }

    /**
     * Fetches the cached entry.
     *
     * @return bool|RepoGithubEntryInterface
     */
    protected function read(RepoGithubEntryInterface $entry)
    {
        if (!$this->supports($entry)) {
            throw new \UnexpectedValueException(sprintf('Should not receive an unsupported entry "%s"', $entry->getId()));
        }

        $path = $this->getPath($entry);
        if (!file_exists($path)) {
            return false;
        }

        $cached = unserialize(file_get_contents($path));
        if (!$this->supports($cached)) {
            throw new \UnexpectedValueException(sprintf('Should not receive an unsupported cached entry "%s"', $cached->getId()));
        }

        return $cached;
    }

    /**
     * Saves an entry to file.
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
     * @return string
     */
    protected function getPath(RepoGithubEntryInterface $entry)
    {
        [$idType, $id] = explode(':', $entry->getId(), 2);

        return $this->workspace->path(['cache/entries', $idType, $id[0], $id]);
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
