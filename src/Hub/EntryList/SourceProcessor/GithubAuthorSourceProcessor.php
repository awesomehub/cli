<?php

namespace Hub\EntryList\SourceProcessor;

use Github\Utils\GithubWrapperInterface;
use Github\Exception\ExceptionInterface as GithubAPIException;
use Hub\EntryList\Source\Source;
use Hub\EntryList\Source\SourceInterface;

/**
 * Fetches github author repos and outputs new entries.
 */
class GithubAuthorSourceProcessor implements SourceProcessorInterface
{
    /**
     * @var GithubWrapperInterface
     */
    protected $github;

    /**
     * Constructor.
     *
     * @param GithubWrapperInterface $github
     */
    public function __construct(GithubWrapperInterface $github)
    {
        $this->github = $github;
    }

    /**
     * {@inheritdoc}
     */
    public function process(SourceInterface $source, \Closure $callback)
    {
        $author = $source->getData();
        if (empty($author) || empty($author['type']) || empty($author['name'])) {
            throw new \UnexpectedValueException(sprintf(
                'Invalid author data scheme; expected [type: user|org, name: string] but got %s',
                var_export($author, true)
            ));
        }

        $callback(self::ON_STATUS_UPDATE, [
            'type'    => 'info',
            'message' => sprintf("Fetching Github author repos '%s/%s'", $author['type'], $author['name']),
        ]);
        try {
            if (in_array($author['type'], ['org', 'organization'])) {
                $repos = $this->github->api('organization/repositories', [
                    $author['name'],
                    'public',
                ], true);
            } else {
                $repos = $this->github->api('user/repositories', [
                    $author['name'],
                    'all',
                ], true);
            }
        } catch (GithubAPIException $e) {
            throw new \RuntimeException(sprintf('Github API request failed; %s', $e->getMessage()), 0, $e);
        }

        // Source options
        $includeForks = $source->getOption('includeAuthorForks', true);

        $entries = [];
        foreach ($repos as $repo) {
            if (!$includeForks && $repo['fork']) {
                continue;
            }

            $entries[] = [
                'type' => 'repo.github',
                'data' => [
                    'author' => $author['name'],
                    'name'   => $repo['name'],
                ],
            ];
        }

        $options = $source->getOptions();
        unset($options['includeAuthorForks']);

        return new Source('entries', $entries, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(SourceInterface $source)
    {
        return $source->getType() === 'github.author'
            ? self::ACTION_PARTIAL_PROCESSING
            : self::ACTION_SKIP;
    }
}
