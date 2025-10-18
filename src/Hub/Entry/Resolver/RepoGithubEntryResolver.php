<?php

declare(strict_types=1);

namespace Hub\Entry\Resolver;

use Hub\CachedContainer;
use Hub\Environment\Environment;
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
class RepoGithubEntryResolver implements EntryResolverInterface, AsyncResolverInterface
{
    public function __construct(protected GithubRepoInspectorInterface $inspector, protected Filesystem $filesystem, protected WorkspaceInterface $workspace) {}

    /**
     * @param RepoGithubEntryInterface $entry
     */
    public function resolve(EntryInterface $entry, bool $force = false): void
    {
        $cached = $this->read($entry);
        if ($cached && !$force) {
            // Only merge the fields that we provide
            $fields = ['author', 'name', 'description', 'language', 'license', 'scores_avg', 'scores', 'pushed', 'archived'];
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
            throw new EntryResolveFailedException(\sprintf('Github Repo Inspector failed; %s', $e->getMessage()), 0, $e);
        }

        $entry->merge([
            'description' => $this->cleanStr((string) $repo['description']),
            'language' => $repo['language'],
            'license' => $repo['license_id'],
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
            throw new EntryResolveFailedException(\sprintf('Failed caching Github repo; %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * @param RepoGithubEntryInterface $entry
     */
    public function supports(EntryInterface $entry): bool
    {
        return $entry instanceof RepoGithubEntryInterface;
    }

    /**
     * @param RepoGithubEntryInterface $entry
     */
    public function isCached(EntryInterface $entry): bool
    {
        $cached = $this->read($entry);

        return $cached instanceof RepoGithubEntryInterface;
    }

    /**
     * Fetches the cached entry.
     */
    protected function read(RepoGithubEntryInterface $entry): bool|RepoGithubEntryInterface
    {
        if (!$this->supports($entry)) {
            throw new \UnexpectedValueException(\sprintf('Should not receive an unsupported entry "%s"', $entry->getId()));
        }

        $path = $this->getPath($entry);
        if (!file_exists($path)) {
            return false;
        }

        $cached = unserialize(file_get_contents($path));
        if (!$this->supports($cached)) {
            throw new \UnexpectedValueException(\sprintf('Should not receive an unsupported cached entry "%s"', $cached->getId()));
        }

        return $cached;
    }

    /**
     * Saves an entry to file.
     */
    protected function save(RepoGithubEntryInterface $entry): int
    {
        $path = $this->getPath($entry);

        return $this->filesystem->write($path, serialize($entry));
    }

    /**
     * Gets the path of the entry cache file.
     */
    protected function getPath(RepoGithubEntryInterface $entry): string
    {
        [$idType, $id] = explode(':', $entry->getId(), 2);

        return $this->workspace->path(['cache/entries', $idType, $id[0], $id]);
    }

    /**
     * Removes all non printable characters in a string.
     */
    protected function cleanStr(string $string): string
    {
        // Strip non-utf chars
        $string = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $string);

        // Strip github emoticons
        return trim(preg_replace('/:[^:]+:/', '', $string));
    }

    public function getAsyncContext(): array
    {
        $bin = $_SERVER['argv'][0] ?? null;
        if (!\is_string($bin) || '' === $bin) {
            $bin = dirname(__DIR__, 4).'/bin/hub';
        }

        return [
            'workspace' => $this->workspace->path(),
            'bin' => $bin,
        ];
    }

    public static function createAsyncResolver(array $context): static
    {
        if (empty($context['workspace']) || !\is_string($context['workspace'])) {
            throw new \InvalidArgumentException('Async resolver context is missing a valid workspace path.');
        }

        $bin = $context['bin'] ?? dirname(__DIR__, 4).'/bin/hub';
        if (!\is_string($bin) || '' === $bin) {
            $bin = dirname(__DIR__, 4).'/bin/hub';
        }

        if (!file_exists($bin)) {
            $bin = dirname(__DIR__, 4).'/bin/hub';
        }

        $_SERVER['argv'] = [$bin, '--workspace', $context['workspace']];
        $_SERVER['argc'] = 3;

        static $containers = [];

        if (!isset($containers[$context['workspace']])) {
            $environment = new Environment();
            $container = new CachedContainer();
            $container->set('environment', $environment);
            $containers[$context['workspace']] = $container;
        }

        /** @var CachedContainer $container */
        $container = $containers[$context['workspace']];

        $workspace = $container->get('workspace');
        $filesystem = $container->get('filesystem');

        try {
            $inspector = $container->get('github.inspector');
        } catch (\UnexpectedValueException $e) {
            if (str_contains($e->getMessage(), 'Unexpected token pool data')) {
                $poolPath = $workspace->path('cache/github/tokens.pool');
                if (file_exists($poolPath)) {
                    $filesystem->write($poolPath, serialize([]), true);
                }

                $inspector = $container->get('github.inspector');
            } else {
                throw $e;
            }
        }

        return new static($inspector, $filesystem, $workspace);
    }
}
