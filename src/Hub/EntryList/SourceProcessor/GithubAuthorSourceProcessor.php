<?php

declare(strict_types=1);

namespace Hub\EntryList\SourceProcessor;

use Github\Exception\ExceptionInterface as GithubAPIException;
use Github\Utils\GithubWrapperInterface;
use Hub\EntryList\Source\Source;
use Hub\EntryList\Source\SourceInterface;

/**
 * Fetches github author repos and outputs new entries.
 */
class GithubAuthorSourceProcessor implements SourceProcessorInterface
{
    public function __construct(protected GithubWrapperInterface $github)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(SourceInterface $source, \Closure $callback)
    {
        $author = $source->getData();
        if (empty($author) || empty($author['type']) || empty($author['name'])) {
            throw new \UnexpectedValueException(sprintf('Invalid author data scheme; expected [type: user|org, name: string] but got %s', var_export($author, true)));
        }

        $callback(self::ON_STATUS_UPDATE, [
            'type' => 'info',
            'message' => sprintf("Fetching Github author repos '%s/%s'", $author['type'], $author['name']),
        ]);

        try {
            if (\in_array($author['type'], ['org', 'organization'])) {
                $repos = $this->github->api('organization/repositories', [
                    $author['name'],
                    'sources',
                ], true);
            } else {
                $repos = $this->github->api('user/repositories', [
                    $author['name'],
                    'owner',
                ], true);
            }
        } catch (GithubAPIException $e) {
            throw new \RuntimeException(sprintf('Github API request failed; %s', $e->getMessage()), 0, $e);
        }

        $entries = [];
        foreach ($repos as $repo) {
            $entries[] = [
                'type' => 'repo.github',
                'data' => [
                    'author' => $author['name'],
                    'name' => $repo['name'],
                ],
            ];
        }

        return new Source('entries', $entries, $source->getOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(SourceInterface $source)
    {
        return 'github.author' === $source->getType()
            ? self::ACTION_PARTIAL_PROCESSING
            : self::ACTION_SKIP;
    }
}
