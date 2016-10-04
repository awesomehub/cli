<?php
namespace Hub\Process;

use Psr\Log\LoggerInterface;

/**
 * A Factory for creating Process instances.
 *
 * @package AwesomeHub
 */
class ProcessFactory implements ProcessFactoryInterface
{
    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Creates a new Process.
     *
     * @param string $command
     * @param array $options Process options, includes:
     * @return Process
     */
    public function create($command, array $options = [])
    {
        return new Process($command, $options, $this->logger);
    }

    /**
     * Creates and runs a new Process.
     *
     * @param string $command
     * @param array $options Process options, includes:
     * @return Process
     */
    public function run($command, array $options = [])
    {
        $proc = $this->create($command, $options);
        $proc->run();

        return $proc;
    }

    /**
     * Creates and starts a new Process.
     *
     * @param string $command
     * @param array $options Process options, includes:
     * @return Process
     */
    public function start($command, array $options = [])
    {
        $proc = $this->create($command, $options);
        $proc->start();

        return $proc;
    }
}
