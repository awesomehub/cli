<?php

namespace Hub\IO;

use Hub\IO\Output\OverwritableOutputInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console;
use Hub\Helper\ProgressIndicator;

/**
 * Base I/O class.
 *
 * @package AwesomeHub
 */
class IO extends Console\Style\SymfonyStyle implements IOInterface
{
    /**
     * @var Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ProgressIndicator
     */
    protected $progressIndicator;

    /**
     * Constructor.
     *
     * @param Console\Input\InputInterface  $input
     * @param Console\Output\OutputInterface $output
     * @param LoggerInterface $logger
     */
    public function __construct(
        Console\Input\InputInterface $input,
        Console\Output\OutputInterface $output,
        LoggerInterface $logger
    )
    {
        $this->input  = $input;
        $this->output = $output;
        $this->logger = $logger;

        parent::__construct($input, $output);
    }

    /**
     * @inheritdoc
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @inheritdoc
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @inheritdoc
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @inheritdoc
     */
    public function startOverwrite(array $options = [])
    {
        if($this->output instanceof OverwritableOutputInterface){
            $this->output->startOverwrite($options);
        }
    }

    /**
     * @inheritdoc
     */
    public function endOverwrite()
    {
        if($this->output instanceof OverwritableOutputInterface){
            $this->output->endOverwrite();
        }
    }

    /**
     * @inheritdoc
     */
    public function isOverwritable()
    {
        if($this->output instanceof OverwritableOutputInterface){
            return $this->output->isOverwritable();
        }

        return false;
    }
}