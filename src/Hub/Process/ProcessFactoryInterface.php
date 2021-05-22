<?php

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
     *
     * @param string $command
     * @param array  $options Process options
     *
     * @return Process
     */
    public function create($command, array $options = []);

    /**
     * Creates and runs a new Process.
     *
     * @param string $command
     * @param array  $options Process options
     *
     * @return Process
     */
    public function run($command, array $options = []);

    /**
     * Creates and starts a new Process.
     *
     * @param string $command
     * @param array  $options Process options
     *
     * @return Process
     */
    public function start($command, array $options = []);
}
