<?php

declare(strict_types=1);

namespace Hub\Process;

use Psr\Log\LoggerInterface;

/**
 * Interface for a ProcessFactory class.
 */
interface ProcessFactoryInterface
{
    /**
     * Constructor.
     */
    public function __construct(LoggerInterface $logger);

    /**
     * Creates a new Process.
     */
    public function create(string $command, array $options = []): Process;

    /**
     * Creates and runs a new Process.
     */
    public function run(string $command, array $options = []): Process;

    /**
     * Creates and starts a new Process.
     *
     * @param mixed $command
     */
    public function start($command, array $options = []): Process;
}
