<?php

declare(strict_types=1);

namespace Hub\Build;

use Hub\Filesystem\Filesystem;
use Hub\Workspace\WorkspaceInterface;

/**
 * Creates and manages builds.
 */
class BuildFactory implements BuildFactoryInterface
{
    protected array $path;

    public function __construct(protected Filesystem $filesystem, protected WorkspaceInterface $workspace)
    {
        $this->path = [
            'dist' => $this->workspace->path('dist'),
            'cached' => $this->workspace->path('cache/dist'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function create($path = null): BuildInterface
    {
        $build = new Build(
            $this->filesystem,
            $path ?: $this->path['dist'],
            $this->getNextBuildNumber()
        );

        $build->clean();

        return $build;
    }

    /**
     * {@inheritdoc}
     */
    public function cache(BuildInterface $build): void
    {
        $this->filesystem->mirror($build->getPath(), $this->path['cached']);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrent(): BuildInterface | null
    {
        try {
            return new Build($this->filesystem, $this->path['dist']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCached(): BuildInterface | null
    {
        try {
            return new Build($this->filesystem, $this->path['cached']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Gets the next build number.
     */
    protected function getNextBuildNumber(): string
    {
        $number = [date('Ymd'), 0];
        $file = $this->workspace->path('.buildnum');
        if (file_exists($file)) {
            $pnumber = explode('.', $this->filesystem->read($file));
            if (2 === \count($pnumber) && $number[0] === $pnumber[0]) {
                $number[1] = (int) $pnumber[1] + 1;
            }
        }

        $number = sprintf('%d.%d', $number[0], $number[1]);
        $this->filesystem->write($file, $number);

        return $number;
    }
}
