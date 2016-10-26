<?php

namespace Hub\Process;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process as BaseProcess;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

/**
 * Process class with additional functionality (eg. logging).
 */
class Process extends BaseProcess
{
    /**
     * @var string
     */
    protected $command;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $isTerminationLogged;

    /**
     * @var bool
     */
    protected $isStartingLogged;

    /**
     * Constructor.
     *
     *  Process oprtions include:
     *      [output]    OutputInterface|ConsoleOutputInterface
     *      [timeout]   int
     *      [input]     string|resource|\Traversable
     *      [env]       array
     *      [cwd]       string
     *
     * @param string          $command
     * @param array           $options Process options:
     * @param LoggerInterface $logger
     */
    public function __construct($command, array $options = [], LoggerInterface $logger = null)
    {
        $this->command = $command;
        $this->options = array_merge([
            'input'   => null,
            'output'  => null,
            'timeout' => 60,
            'env'     => null,
            'cwd'     => null,
        ], $options);
        $this->logger = $logger;

        $this->logger->debug("[Process] Initializing ($this->command)");

        parent::__construct(
            $this->command,
            $this->options['cwd'],
            $this->options['env'],
            $this->options['input'],
            $this->options['timeout']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function start(callable $callback = null)
    {
        return parent::start($this->getOutputCallback($callback));
    }

    /**
     * {@inheritdoc}
     */
    public function wait(callable $callback = null)
    {
        return parent::wait($this->getOutputCallback($callback));
    }

    /**
     * {@inheritdoc}
     */
    public function stop($timeout = 10, $signal = null)
    {
        if (!$this->isTerminationLogged) {
            $this->isTerminationLogged = true;
            $this->logger->debug("[Process] Stopping ($this->command)");
        }

        return parent::stop($timeout, $signal);
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning()
    {
        $running = parent::isRunning();

        if ($this->isTerminated() && !$this->isTerminationLogged) {
            $this->isTerminationLogged = true;

            if ($this->isSuccessful()) {
                $this->logger->debug("[Process] Execution Successfull ($this->command)");
            } else {
                $this->logger->error("[Process] Execution failed ($this->command)");
            }
        }

        return $running;
    }

    /**
     * Adds output callback to the user defined callback. Logs the starting message.
     *
     * @param callable|null $callback The user defined PHP callback
     *
     * @return \Closure A PHP closure
     */
    protected function getOutputCallback(callable $callback = null)
    {
        if (!$this->isStartingLogged) {
            $this->isStartingLogged = true;
            $this->logger->debug("[Process] Starting ($this->command)");
        }

        if (!is_callable($callback)) {
            $callback = null;
        }

        $finalCallback = $callback;
        /* @var $output OutputInterface|ConsoleOutputInterface */
        $output = $this->options['output'];
        if ($output instanceof OutputInterface) {
            $finalCallback = function ($type, $buffer) use ($output, $callback) {
                if ($callback) {
                    call_user_func($callback, $type, $buffer);
                }

                if ($type === Process::ERR && $output instanceof ConsoleOutputInterface) {
                    $output->getErrorOutput()->write($buffer);

                    return;
                }

                $output->write($buffer);
            };
        }

        return $finalCallback;
    }
}
