<?php

namespace Hub\Command;

use Hub\Build\BuildFactory;
use Hub\EntryList\Distributer\ListDistributer;
use Hub\EntryList\EntryListFile;
use Symfony\Component\Console\Input;

/**
 * Distribute a cached list.
 */
class MakeBuildCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('make:build')
            ->setDescription('Distributes a new build.')
            ->addOption(
                '--release', '-r', Input\InputOption::VALUE_NONE, 'Marks the build as a release'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function validate()
    {
        if ($this->input->getOption('release')) {
            $yes = $this->io->confirm(sprintf('Are you sure you want to mark this build as a release?'));
            if (!$yes) {
                exit(0);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function exec()
    {
        $buildFactory = new BuildFactory($this->filesystem, $this->workspace);
        $build        = $buildFactory->create();
        $cachedBuild  = $buildFactory->getCached() ?: null;
        $lists        = EntryListFile::findCachedLists($this->workspace);

        if (count($lists) == 0) {
            $this->io->note('No cached lists found');

            return 0;
        }

        $this->io->title('Building distributable lists');
        $this->io->writeln([
            ' <comment>* Lists count:</comment> '.count($lists),
            ' <comment>* Build number:</comment> '.$build->getNumber(),
            ' <comment>* Build path:</comment> '.$build->getPath(),
            ' <comment>* Build format:</comment> '.$build->getFormat(),
            '',
        ]);

        $this->logger->info(sprintf('Initiating list distributer %s cached build', $cachedBuild ? 'with' : 'without'));
        $dist = new ListDistributer($build, $cachedBuild, [
            'collections' => $this->workspace->config('dist.listCollections'),
        ]);
        foreach ($lists as $list) {
            try {
                $this->logger->info(sprintf("Building list '%s'", $list));
                $listInstance = EntryListFile::createFromCache($this->filesystem, $this->workspace, $list);
                $dist->distribute($listInstance);
            } catch (\Exception $e) {
                $this->logger->critical(sprintf("Unable to build list '%s'; %s", $list, $e->getMessage()));
            }
        }

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
}
