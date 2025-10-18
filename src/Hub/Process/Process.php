<?php

declare(strict_types=1);

namespace Hub\Process;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process as BaseProcess;

/**
 * Process class with additional functionality (eg. logging).
 */
class Process extends BaseProcess
{
    protected string $command;
    protected ?LoggerInterface $logger;
    protected ?OutputInterface $output;
    protected array $options;
    protected bool $isTerminationLogged;
    protected bool $isStartingLogged;

    /**
     * Constructor.
     *
     *  Process options include:
     *      [output]    null|OutputInterface
     *      [logger]    null|LoggerInterface
     *      [timeout]   int
     *      [input]     string|resource|\Traversable
     *      [env]       array
     *      [cwd]       string
     */
    public function __construct(array $command, array $options = [])
    {
        $this->options = array_merge([
            'input' => null,
            'output' => null,
            'logger' => null,
            'timeout' => 60,
            'env' => null,
            'cwd' => null,
        ], $options);

        $this->logger = $this->options['logger'];
        $this->output = $this->options['output'];

        $this->command = \sprintf('[%s]', implode(', ', $command));
        $this->logger && $this->logger->debug("[Process] Initializing ({$this->command})");

        parent::__construct(
            $command,
            $this->options['cwd'],
            $this->options['env'],
            $this->options['input'],
            $this->options['timeout']
        );
    }

    public function start(?callable $callback = null): void
    {
        parent::start($this->getOutputCallback($callback));
    }

    public function wait(?callable $callback = null): int
    {
        return parent::wait($this->getOutputCallback($callback));
    }

    public function stop($timeout = 10, $signal = null): ?int
    {
        if (!$this->isTerminationLogged) {
            $this->isTerminationLogged = true;
            $this->logger && $this->logger->debug("[Process] Stopping ({$this->command})");
        }

        return parent::stop($timeout, $signal);
    }

    public function isRunning(): bool
    {
        $running = parent::isRunning();

        if (!$this->isTerminationLogged && $this->isTerminated()) {
            $this->isTerminationLogged = true;

            if ($this->isSuccessful()) {
                $this->logger && $this->logger->debug("[Process] Execution successful ({$this->command})");
            } else {
                $this->logger && $this->logger->error("[Process] Execution failed ({$this->command})");
            }
        }

        return $running;
    }

    /**
     * Adds output callback to the user defined callback and logs the starting message.
     *
     * @param null|callable $callback The user defined PHP callback
     */
    protected function getOutputCallback(?callable $callback = null): ?callable
    {
        if (!$this->isStartingLogged) {
            $this->isStartingLogged = true;
            $this->logger && $this->logger->debug("[Process] Starting ({$this->command})");
        }

        if (!\is_callable($callback)) {
            $callback = null;
        }

        $finalCallback = $callback;
        if ($this->output) {
            $finalCallback = function ($type, $buffer) use ($callback): void {
                if ($callback) {
                    $callback($type, $buffer);
                }

                if (self::ERR === $type && $this->output instanceof ConsoleOutputInterface) {
                    $this->output->getErrorOutput()->write($buffer);

                    return;
                }

                $this->output->write($buffer);
            };
        }

        return $finalCallback;
    }
}
