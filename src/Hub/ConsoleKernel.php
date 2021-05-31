<?php

declare(strict_types=1);

namespace Hub;

use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * Console Kernel.
 */
class ConsoleKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    protected function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load('services.xml');
    }
}
