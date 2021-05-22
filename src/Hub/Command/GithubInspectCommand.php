<?php

namespace Hub\Command;

use Github\Utils\RepoInspector;
use Symfony\Component\Console\Input;

/**
 * Inspects github repository.
 */
class GithubInspectCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('github:inspect')
            ->setDescription('Inspects github repository.')
            ->addArgument(
                'repo',
                Input\InputArgument::REQUIRED,
                'Github repository to be inspeced, should be formatted like {author}/{name}'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function exec()
    {
        /** @var RepoInspector\GithubRepoInspectorInterface $inspector */
        $inspector = $this->container->get('github.inspector');
        $name = explode('/', trim($this->input->getArgument('repo')), 2);
        if (2 != \count($name)) {
            $this->io->error('Invalid Github repository provided. It should be formatted like {author}/{name}');

            return 1;
        }

        try {
            $repo = $inspector->inspect($name[0], $name[1]);
        } catch (RepoInspector\Exception\RepoInspectorException $e) {
            $this->io->error(sprintf('Github Inspector failed; %s', $e->getMessage()));

            return 1;
        }

        $this->io->section(sprintf('Repository: %s', $repo['full_name']));

        $list = [
            sprintf(' <info>* URL:</info> %s', $repo['url']),
            sprintf(' <info>* Language:</info> %s', $repo['language'] ?? 'None'),
            sprintf(' <info>* License:</info> %s', $repo['license_id'] ?? 'None'),
            sprintf(' <info>* Created:</info> %s', date('Y-m-d', strtotime($repo['created_at']))),
            sprintf(' <info>* Pushed:</info> %s', date('Y-m-d g:i:s A e', strtotime($repo['pushed_at']))),
            sprintf(' <info>* Average Score:</info> %d', $repo['scores_avg']),
            ' <info>* Scores:</info>',
            sprintf('   <debug>- [P] Popularity:</debug> %d', $repo['scores']['p']),
            sprintf('   <debug>- [H] Hotness:</debug> %d', $repo['scores']['h']),
            sprintf('   <debug>- [A] Activity:</debug> %d', $repo['scores']['a']),
            sprintf('   <debug>- [M] Maturity:</debug> %d', $repo['scores']['m']),
            ' <info>* Stats:</info>',
            sprintf('   <debug>- Stars:</debug> %d', $repo['stargazers_count']),
            sprintf('   <debug>- Forks:</debug> %d', $repo['forks_count']),
            sprintf('   <debug>- Subscribers:</debug> %d', $repo['subscribers_count']),
            sprintf('   <debug>- Commits:</debug> %d', $repo['commits_count']),
            sprintf('   <debug>- Branches:</debug> %d', $repo['branches_count']),
            sprintf('   <debug>- Contributors:</debug> %d', $repo['contributors_count']),
            sprintf('   <debug>- Releases:</debug> %d', $repo['releases_count']),
            sprintf('   <debug>- Size:</debug> %sM', round($repo['size'] / 1024, 1)),
        ];

        if (!empty($repo['description'])) {
            array_unshift($list, sprintf(' <info>* Description:</info> %s', $repo['description']));
        }

        $this->io->writeln($list);
        $this->io->writeln('');

        return 0;
    }
}
