<?php

namespace Hub\EntryList\SourceProcessor;

use Hub\EntryList\Source\Source;
use Hub\EntryList\Source\SourceInterface;

/**
 * Adds an array of github repos.
 */
class GithubReposSourceProcessor implements SourceProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(SourceInterface $source, \Closure $callback)
    {
        $repos = $source->getData();
        if (!\is_array($repos)) {
            throw new \UnexpectedValueException(sprintf('Unexpected github repos source data type; Expected [array] but got [%s]', \gettype($repos)));
        }

        $entries = [];
        foreach ($repos as $repo) {
            $segments = explode('/', $repo, 2);
            if (2 !== \count($segments)) {
                throw new \RuntimeException(sprintf("Incorrect github repo provided '%s'", $repo));
            }

            $entries[] = [
                'type' => 'repo.github',
                'data' => [
                    'author' => $segments[0],
                    'name' => $segments[1],
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
        return 'github.repos' === $source->getType()
            ? self::ACTION_PARTIAL_PROCESSING
            : self::ACTION_SKIP;
    }
}
