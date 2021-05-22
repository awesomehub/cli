<?php

namespace Hub\IO;

use Hub\IO\Output\OverwritableOutputInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console;

/**
 * Interface for an IO class.
 */
interface IOInterface extends Console\Output\OutputInterface, Console\Style\StyleInterface, OverwritableOutputInterface
{
    /**
     * @return Console\Input\InputInterface
     */
    public function getInput();

    /**
     * @return Console\Output\OutputInterface
     */
    public function getOutput();

    /**
     * @return LoggerInterface
     */
    public function getLogger();
}
