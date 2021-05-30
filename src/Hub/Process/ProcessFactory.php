<?php

namespace Hub\Process;

use Psr\Log\LoggerInterface;

/**
 * A Factory for creating Process instances.
 */
class ProcessFactory implements ProcessFactoryInterface
{
    protected LoggerInterface $logger;

    /**
     * {@inheritdoc}
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
