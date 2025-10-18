<?php

declare(strict_types=1);

namespace Hub\Process;

use Psr\Log\LoggerInterface;

/**
 * Helper class for creating and running processes.
 */
class ProcessFactory implements ProcessFactoryInterface
{
    public function __construct(protected LoggerInterface $logger) {}

    public function create(array $command, array $options = []): Process
    {
        return new Process($command, array_merge(['logger' => $this->logger], $options));
    }

    public function run(array $command, array $options = []): Process
    {
        $proc = $this->create($command, $options);
        $proc->run();

        return $proc;
    }

    public function start(array $command, array $options = []): Process
    {
        $proc = $this->create($command, $options);
        $proc->start();

        return $proc;
    }
}
