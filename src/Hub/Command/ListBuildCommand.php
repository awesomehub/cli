<?php

declare(strict_types=1);

namespace Hub\Command;

use Hub\Entry\Factory\TypeEntryFactory;
use Hub\Entry\Factory\UrlEntryFactory;
use Hub\Entry\Factory\UrlProcessor\GithubUrlProcessor;
use Hub\Entry\Resolver\RepoGithubEntryResolver;
use Hub\EntryList\EntryListFile;
use Hub\EntryList\EntryListInterface;
use Hub\EntryList\SourceProcessor\EntriesSourceProcessor;
use Hub\EntryList\SourceProcessor\GithubAuthorSourceProcessor;
use Hub\EntryList\SourceProcessor\GithubListSourceProcessor;
use Hub\EntryList\SourceProcessor\GithubMarkdownSourceProcessor;
use Hub\EntryList\SourceProcessor\GithubReposSourceProcessor;
use Hub\EntryList\SourceProcessor\UrlListSourceProcessor;
use Symfony\Component\Console\Input;

/**
 * Builds a given list.
 */
class ListBuildCommand extends Command
{
    protected EntryListInterface $list;

    /**
     * {@inheritdoc}
     */
    public function validate(): void
    {
        if (null === $this->input->getArgument('list')) {
            $all = $this->io->confirm('Are you sure you want to build all lists?');
            if (!$all) {
                exit(0);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('list:build')
            ->setDescription('Build a hub list using a given list definition file')
            ->addArgument(
                'list',
                Input\InputArgument::OPTIONAL,
                'The name or path to the list definition file'
            )
            ->addOption(
                '--format',
                '-f',
                Input\InputOption::VALUE_REQUIRED,
                'The list file format (json or yaml)',
                'json'
            )
            ->addOption(
                '--no-resolve',
                null,
                Input\InputOption::VALUE_NONE,
                'Do not resolve the list'
            )
            ->addOption(
                '--no-cache',
                null,
                Input\InputOption::VALUE_NONE,
                'Do not check for cached entries (may slow down the build)'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function exec(): int
    {
        $path = $this->input->getArgument('list');
        $format = strtolower($this->input->getOption('format'));
        $noResolve = $this->input->getOption('no-resolve');
        $noCache = $this->input->getOption('no-cache');

        // Build all lists
        if (empty($path)) {
            $paths = EntryListFile::findLists($this->workspace);
            if ([] === $paths) {
                $this->io->note('No lists found to build');

                exit(0);
            }

            $this->io->title(sprintf('Building %d list(s)', \count($paths)));
            foreach ($paths as $singlePath) {
                try {
                    $this->build($singlePath, $format, $noResolve, $noCache);
                } catch (\Exception $e) {
                    $this->io->getLogger()->warning(sprintf(
                        "Ignoring list '%s'; %s",
                        $singlePath,
                        $e->getMessage()
                    ));
                }
            }

            $this->io->writeln('');

            return 0;
        }

        // Build a single list
        $this->build($path, $format, $noResolve, $noCache);

        // We're done
        $this->io->writeln('');

        return 0;
    }

    /**
     * Builds a list.
     */
    protected function build(string $path, string $format, bool $noResolve, bool $noCache): void
    {
        $list = new EntryListFile($this->filesystem, $this->workspace, $path, $format);
        $this->io->title('Building list: '.$path);
        $this->process($list);
        if (!$noResolve) {
            $this->resolve($list, $noCache);
        }
        $list->finalize($this->io);
        $this->logger->info('Done!');
    }

    /**
     * Processes the list.
     */
    protected function process(EntryListInterface $list): void
    {
        // Create needed entry factories
        $entryFromUrlFactory = new UrlEntryFactory([
            new GithubUrlProcessor(),
        ]);
        $entryFromTypeFactory = new TypeEntryFactory();

        // Do the actual processing
        $list->process($this->io, [
            new GithubReposSourceProcessor(),
            new GithubAuthorSourceProcessor($this->container->get('github')),
            new GithubListSourceProcessor($this->container->get('http')),
            new GithubMarkdownSourceProcessor(),
            new UrlListSourceProcessor($entryFromUrlFactory),
            new EntriesSourceProcessor($entryFromTypeFactory),
        ]);
    }

    /**
     * Resolves the list.
     */
    protected function resolve(EntryListInterface $list, bool $force = false): void
    {
        $list->resolve($this->io, [
            new RepoGithubEntryResolver($this->container->get('github.inspector'), $this->filesystem, $this->workspace),
        ], $force);
    }
}
