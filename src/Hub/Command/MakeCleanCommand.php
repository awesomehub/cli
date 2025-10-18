<?php

declare(strict_types=1);

namespace Hub\Command;

use Hub\Build\BuildFactory;

/**
 * Cleans the dist directory.
 */
class MakeCleanCommand extends Command
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('make:clean')
            ->setDescription('Clean the dist directory')
        ;
    }

    protected function exec(): int
    {
        $buildFactory = new BuildFactory($this->filesystem, $this->workspace);
        $build = $buildFactory->getCurrent();
        if (null !== $build) {
            $build->clean();
        }

        return 0;
    }
}
