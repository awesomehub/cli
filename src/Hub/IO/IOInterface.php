<?php

namespace Hub\IO;

use Hub\IO\Output\OverwritableOutputInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface for an IO class.
 */
interface IOInterface extends OutputInterface, StyleInterface, OverwritableOutputInterface
{
    public function getInput(): InputInterface;

    public function getOutput(): OutputInterface;

    public function getLogger(): LoggerInterface;
}
