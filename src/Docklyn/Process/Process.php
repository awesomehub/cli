<?php
namespace Docklyn\Process;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process as BaseProcess;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class Process extends BaseProcess
{
    /**
     * @var $command string
     */
    protected $command;

    /**
     * @var $options array
     */
    protected $options;

    /**
     * @var $logger LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     *  Process oprtions include:
     *      [output]    OutputInterface|ConsoleOutputInterface
     *      [callback]  callable
     *      [timeout]   int
     *      [input]     string|resource|\Traversable
     *      [env]       array
     *      [cwd]       string
     *
     * @param string $command
     * @param array $options Process options:
     * @param LoggerInterface $logger
     */
    public function __construct($command, array $options = [], LoggerInterface $logger = null)
    {
        $this->command = $command;
        $this->options = array_merge([
            'input'     => null,
            'output'    => null,
            'callback'  => null,
            'timeout'   => 60,
            'env'       => null,
            'cwd'       => null,
        ], $options);
        $this->logger = $logger;

        $this->logger->debug("[Process] Initializing ($this->command)");

        return parent::__construct(
            $this->command,
            $this->options['cwd'],
            $this->options['env'],
            $this->options['input'],
            $this->options['timeout']
        );
    }

    /**
     * Runs the process.
     *
     * @param callable|null $callback
     * @return int The exit status code
     */
    public function start($callback = null)
    {
        $this->logger->debug("[Process] Starting ($this->command)");

        $finalCallback = null;
        /* @var $output OutputInterface|ConsoleOutputInterface */
        $output = $this->options['output'];
        if($output instanceof OutputInterface){
            $finalCallback = function ($type, $buffer) use($output, $callback) {
                if($callback){
                    call_user_func($callback, $type, $buffer);
                }

                if($type === Process::ERR && $output instanceof ConsoleOutputInterface){
                    $output->getErrorOutput()->write($buffer);
                    return;
                }

                $output->write($buffer);
            };
        }
        else if ($callback){
            $finalCallback = $callback;
        }

        return parent::start($finalCallback);
    }

    /**
     * Updates the status of the process.
     *
     * @param bool $blocking Whether to use a blocking read call
     */
    protected function updateStatus($blocking)
    {
        parent::updateStatus($blocking);

        if(!$this->isTerminated()){
            return;
        }

        if($this->isSuccessful()){
            $this->logger->debug("[Process] Execution Successfull ($this->command)");
        }
        else {
            $this->logger->error("[Process] Execution failed ($this->command)");
        }
    }
}
