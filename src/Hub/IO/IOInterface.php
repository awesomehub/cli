<?php

declare(strict_types=1);

namespace Hub\IO;

use Hub\IO\Output\OverwritableOutputInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * Interface for an IO class.
 */
interface IOInterface extends OutputInterface, StyleInterface, OverwritableOutputInterface
{
    public function getInput(): InputInterface;

    public function getOutput(): OutputInterface;

    public function getLogger(): LoggerInterface;
}
