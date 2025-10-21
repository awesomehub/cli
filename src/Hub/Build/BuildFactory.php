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

    public function cache(BuildInterface $build): void
    {
        $this->filesystem->mirror($build->getPath(), $this->path['cached']);
    }

    public function getCurrent(): ?BuildInterface
    {
        try {
            return new Build($this->filesystem, $this->path['dist']);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getCached(): ?BuildInterface
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
        $date = date('Ymd');
        $increment = 0;

        $currentBuild = $this->getCurrent();
        if ($currentBuild instanceof BuildInterface) {
            $currentNumber = $currentBuild->getNumber();
            $parts = explode('.', $currentNumber);
            if (2 === \count($parts) && $parts[0] === $date) {
                $increment = (int) $parts[1] + 1;
            }
        }

        return \sprintf('%s.%d', $date, $increment);
    }
}
