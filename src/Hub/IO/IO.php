<?php

declare(strict_types=1);

namespace Hub\IO;

use Hub\IO\Output\OverwritableOutputInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Base I/O class.
 */
class IO extends SymfonyStyle implements IOInterface
{
    public function __construct(protected InputInterface $input, protected OutputInterface $output, protected LoggerInterface $logger)
    {
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
