<?php

declare(strict_types=1);

namespace Hub\Process;

use Psr\Log\LoggerInterface;

/**
 * A Factory for creating Process instances.
 */
class ProcessFactory implements ProcessFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $command, array $options = []): Process
    {
        return new Process($this->logger, $command, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function run(string $command, array $options = []): Process
    {
        $proc = $this->create($command, $options);
        $proc->run();

        return $proc;
    }

    /**
     * {@inheritdoc}
     */
    public function start($command, array $options = []): Process
    {
        $proc = $this->create($command, $options);
        $proc->start();

        return $proc;
    }
}
