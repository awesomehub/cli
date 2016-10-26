<?php

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
    protected function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load('services.xml');
    }
}
