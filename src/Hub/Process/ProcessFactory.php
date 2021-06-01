<?php

declare(strict_types=1);

namespace Hub\Process;

use Psr\Log\LoggerInterface;

/**
 * Helper class for creating and running processes.
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
    public function create(array $command, array $options = []): Process
    {
        return new Process($command, array_merge(['logger' => $this->logger], $options));
    }

    /**
     * {@inheritdoc}
     */
    public function run(array $command, array $options = []): Process
    {
        $proc = $this->create($command, $options);
        $proc->run();

        return $proc;
    }

    /**
     * {@inheritdoc}
     */
    public function start(array $command, array $options = []): Process
    {
        $proc = $this->create($command, $options);
        $proc->start();

        return $proc;
    }
}
