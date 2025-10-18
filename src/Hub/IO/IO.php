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

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function startOverwrite(array $options = []): void
    {
        if ($this->output instanceof OverwritableOutputInterface) {
            $this->output->startOverwrite($options);
        }
    }

    public function endOverwrite(): void
    {
        if ($this->output instanceof OverwritableOutputInterface) {
            $this->output->endOverwrite();
        }
    }

    public function isOverwritable(): bool
    {
        if ($this->output instanceof OverwritableOutputInterface) {
            return $this->output->isOverwritable();
        }

        return false;
    }
}
