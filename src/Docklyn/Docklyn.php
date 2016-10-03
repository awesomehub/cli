<?php
namespace Docklyn;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Psr\Log\LoggerInterface;
use Docklyn\Exception\ExceptionHandlerManagerInterface;
use Docklyn\Filesystem\Filesystem;
use Docklyn\Process\ProcessFactoryInterface;

class Docklyn
{
    const VERSION = '0.1.0';

    /**
     * @var Application
     */
    private $application;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ExceptionHandlerManagerInterface
     */
    private $exceptionHandler;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ProcessFactoryInterface
     */
    private $processFactory;

    /**
     * Runs Docklyn.
     *
     * @return int 0 if everything went fine, or an error code
     */
    public function run()
    {
        // Prevent symfony from catching exceptions if an exception handler manager has been registered
        if($this->exceptionHandler instanceof ExceptionHandlerManagerInterface){
            $this->application->setCatchExceptions(false);
        }

        return $this->application->run($this->input, $this->output);
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param Application $application
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input)
    {
        # Map all incoming git-* commands to git:shell
        $command = $input->getFirstArgument();
        if(0 === strpos($command, 'git-')){
            if(!method_exists($input, '__toString')){
                throw new \InvalidArgumentException('The input instance provided must implement __toString() method.');
            }

            $input = new ArrayInput([
                'command' => 'git:shell',
                'git-command' => (string) $input,
            ]);
        }

        $this->input = $input;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return ExceptionHandlerManagerInterface
     */
    public function getExceptionHandler()
    {
        return $this->exceptionHandler;
    }

    /**
     * @param ExceptionHandlerManagerInterface $exception_handler
     */
    public function setExceptionHandler(ExceptionHandlerManagerInterface $exception_handler)
    {
        $this->exceptionHandler = $exception_handler;
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @param Filesystem $filesystem
     */
    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return ProcessFactoryInterface
     */
    public function getProcessFactory()
    {
        return $this->processFactory;
    }

    /**
     * @param ProcessFactoryInterface $facory
     */
    public function setProcessFactory(ProcessFactoryInterface $facory)
    {
        $this->processFactory = $facory;
    }
}
