<?php

namespace Hub\Command;

use Hub\Build\BuildFactory;

/**
 * Cleans the dist directory.
 */
class MakeCleanCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('make:clean')
            ->setDescription('Clean the dist directory.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function exec()
    {
        $buildFactory = new BuildFactory($this->filesystem, $this->workspace);
        $build        = $buildFactory->getCurrent();
        if ($build) {
            $build->clean();
        }

        return 0;
    }
}
