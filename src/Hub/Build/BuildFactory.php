<?php

namespace Hub\Build;

use Hub\Filesystem\Filesystem;
use Hub\Workspace\WorkspaceInterface;

/**
 * Creates and manages builds.
 */
class BuildFactory implements BuildFactoryInterface
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var WorkspaceInterface
     */
    protected $workspace;

    /**
     * @var string
     */
    protected $path;

    /**
     * Constructor.
     */
    public function __construct(Filesystem $filesystem, WorkspaceInterface $workspace)
    {
        $this->filesystem = $filesystem;
        $this->workspace = $workspace;
        $this->path = [
            'dist' => $this->workspace->path('dist'),
            'cached' => $this->workspace->path('cache/dist'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function create($path = null)
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
    public function cache(BuildInterface $build)
    {
        $this->filesystem->mirror($build->getPath(), $this->path['cached']);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrent()
    {
        try {
            return new Build($this->filesystem, $this->path['dist']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCached()
    {
        try {
            return new Build($this->filesystem, $this->path['cached']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Gets the next build number.
     */
    protected function getNextBuildNumber()
    {
        $number = [date('Ymd'), 0];
        $file = $this->workspace->path('.buildnum');
        if (file_exists($file)) {
            $pnumber = explode('.', $this->filesystem->read($file));
            if (2 === \count($pnumber) && $number[0] == $pnumber[0]) {
                $number[1] = $pnumber[1] + 1;
            }
        }

        $number = sprintf('%d.%d', $number[0], $number[1]);
        $this->filesystem->write($file, $number);

        return $number;
    }
}
