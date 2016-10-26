<?php

namespace Hub\IO;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console;
use Hub\IO\Output\OverwritableOutputInterface;

/**
 * Interface for an IO class.
 *
 * @package AwesomeHub
 */
interface IOInterface extends Console\Output\OutputInterface, Console\Style\StyleInterface, OverwritableOutputInterface
{
    /**
     * @return Console\Input\InputInterface
     */
    function getInput();

    /**
     * @return Console\Output\OutputInterface
     */
    function getOutput();

    /**
     * @return LoggerInterface
     */
    function getLogger();
}