<?php
namespace Hub;

use Psr\Log\LoggerInterface;
use Http\Client\HttpClient;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Hub\Exception\ExceptionHandlerManagerInterface;
use Hub\Process\ProcessFactoryInterface;
use Hub\Environment\Environment;
use Hub\Filesystem\Filesystem;

/**
 * The DI Container for the application.
 *
 * @package AwesomeHub
 */
class Container
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var StyleInterface
     */
    private $style;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HttpClient
     */
    private $http;

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
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param Environment $environment
     */
    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;
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
     * @return StyleInterface
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param StyleInterface $style
     */
    public function setStyle(StyleInterface $style)
    {
        $this->style = $style;
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
     * @return HttpClient
     */
    public function getHttp()
    {
        return $this->http;
    }

    /**
     * @param HttpClient $http
     */
    public function setHttp(HttpClient $http)
    {
        $this->http = $http;
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
