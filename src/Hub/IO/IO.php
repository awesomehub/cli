<?php

namespace Hub\IO;

use Hub\IO\Output\OverwritableOutputInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base I/O class.
 */
class IO extends SymfonyStyle implements IOInterface
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected LoggerInterface $logger;

    public function __construct(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $this->input = $input;
        $this->output = $output;
        $this->logger = $logger;

        parent::__construct($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     */
    public function startOverwrite(array $options = []): void
    {
        if ($this->output instanceof OverwritableOutputInterface) {
            $this->output->startOverwrite($options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function endOverwrite(): void
    {
        if ($this->output instanceof OverwritableOutputInterface) {
            $this->output->endOverwrite();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOverwritable(): bool
    {
        if ($this->output instanceof OverwritableOutputInterface) {
            return $this->output->isOverwritable();
        }

        return false;
    }
}
