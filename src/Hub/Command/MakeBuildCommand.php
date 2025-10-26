<?php

declare(strict_types=1);

namespace Hub\Command;

use Hub\Build\BuildFactory;
use Hub\Build\BuildInterface;
use Hub\EntryList\Distributor\ListDistributor;
use Hub\EntryList\EntryListFile;
use Hub\EntryList\EntryListInterface;
use Symfony\Component\Console\Input;

/**
 * Distribute a cached list.
 */
class MakeBuildCommand extends Command
{
    private const DEFAULT_BASE_URL = 'https://awesomehub.js.org';

    public function validate(): void
    {
        if ($this->input->getOption('release')) {
            $yes = $this->io->confirm('Are you sure you want to mark this build as a release?');
            if (!$yes) {
                exit(0);
            }
        }
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('make:build')
            ->setDescription('Distributes a new build')
            ->addArgument(
                'url',
                Input\InputArgument::OPTIONAL,
                'Base URL used when generating the sitemap',
                self::DEFAULT_BASE_URL
            )
            ->addOption(
                '--format',
                '-f',
                Input\InputOption::VALUE_OPTIONAL,
                'Build output format',
                'json'
            )
            ->addOption(
                '--hash',
                '',
                Input\InputOption::VALUE_NEGATABLE,
                'Whether to hash versioned build output files',
                true
            )
            ->addOption(
                '--release',
                '-r',
                Input\InputOption::VALUE_NONE,
                'Marks the build as a release'
            )
        ;
    }

    protected function exec(): int
    {
        $buildFactory = new BuildFactory($this->filesystem, $this->workspace, (string) $this->input->getOption('format'));
        $build = $buildFactory->create(hashed: (bool) $this->input->getOption('hash'));
        $cachedBuild = $buildFactory->getCached() ?: null;
        $lists = EntryListFile::findCachedLists($this->workspace);
        $baseUrl = $this->resolveBaseUrl();
        $sitemapPaths = ['/'];

        if ([] === $lists) {
            $this->io->note('No cached lists found');

            return 0;
        }

        $this->io->title('Building distributable lists');
        $this->io->writeln([
            ' <comment>* Lists count:</comment> '.\count($lists),
            ' <comment>* Build number:</comment> '.$build->getNumber(),
            ' <comment>* Build path:</comment> '.$build->getPath(),
            ' <comment>* Build format:</comment> '.$build->getFormat(),
            '',
        ]);

        $this->logger->info(\sprintf('Initiating list distributor %s cached build', null !== $cachedBuild ? 'with' : 'without'));
        $dist = new ListDistributor($build, $cachedBuild, [
            'collections' => $this->workspace->config('dist.listCollections', []),
        ]);
        foreach ($lists as $list) {
            try {
                $this->logger->info(\sprintf("Building list '%s'", $list));
                $listInstance = EntryListFile::createFromCache($this->filesystem, $this->workspace, $list);
                $dist->distribute($listInstance);
                $sitemapPaths = array_merge($sitemapPaths, $this->generateSitemapPaths($listInstance));
            } catch (\Exception $e) {
                $this->logger->critical(\sprintf("Unable to build list '%s'; %s", $list, $e->getMessage()));
            }
        }

        $sitemapPath = $this->writeSitemap($build, $baseUrl, $sitemapPaths);
        $this->writeRobots($build, $baseUrl, $sitemapPath);
        $dist->finalize();

        $this->logger->info('Finalizing build');
        $build->finalize();

        if ($this->input->getOption('release')) {
            $this->logger->info('Caching build');
            $buildFactory->cache($build);
        }

        $this->logger->info('Done!');
        $this->io->writeln('');

        return 0;
    }

    /**
     * Normalizes the provided base URL and falls back to the default when empty.
     */
    private function resolveBaseUrl(): string
    {
        $url = trim((string) $this->input->getArgument('url'));

        if ('' === $url) {
            $url = self::DEFAULT_BASE_URL;
        }

        return rtrim($url, '/');
    }

    /**
     * Builds sitemap paths for the provided list.
     *
     * @return string[]
     */
    private function generateSitemapPaths(EntryListInterface $list): array
    {
        $listId = $list->getId();
        $paths = [
            $this->normalizeSitemapPath(\sprintf('/list/%s', $listId)),
            $this->normalizeSitemapPath(\sprintf('/list/%s/all', $listId)),
        ];

        foreach ($list->getCategories() as $category) {
            if ('' === trim((string) $category['path'])) {
                continue;
            }

            $paths[] = $this->normalizeSitemapPath(\sprintf('/list/%s/%s', $listId, $category['path']));
        }

        return $paths;
    }

    private function normalizeSitemapPath(string $path): string
    {
        $path = trim($path);
        if ('' === $path) {
            return '/';
        }

        return '/'.ltrim($path, '/');
    }

    /**
     * Writes a sitemap.xml file for the build.
     *
     * @param string[] $paths
     */
    private function writeSitemap(BuildInterface $build, string $baseUrl, array $paths): string
    {
        $paths = array_values(array_unique($paths));
        sort($paths);

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($paths as $path) {
            $loc = htmlspecialchars($baseUrl.$path, \ENT_XML1);
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.$loc.'</loc>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';
        $lines[] = '';

        $relativePath = 'sitemap.xml';
        $this->logger->info(\sprintf("Writing sitemap with %d url(s) to '%s'", \count($paths), $relativePath));
        $build->write($relativePath, implode("\n", $lines), true);

        return $relativePath;
    }

    private function writeRobots(BuildInterface $build, string $baseUrl, string $sitemapPath): void
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Sitemap: '.$baseUrl.'/'.$sitemapPath,
            'Host: '.$baseUrl,
            '',
        ];

        $this->logger->info('Writing robots.txt');
        $build->write('robots.txt', implode("\n", $lines), true);
    }
}
