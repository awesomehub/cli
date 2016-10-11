<?php
namespace Hub;

use Symfony\Component\Console;
use Http\Client\HttpClient;
use Http\Client\Common\HttpMethodsClient;
use Http\Adapter\Guzzle6;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Hub\Util\OutputFormatter;
use Hub\Logger\LoggerManagerInterface;
use Hub\Logger\LoggerManager;
use Hub\Exception\ExceptionHandlerManagerInterface;
use Hub\Exception\ExceptionHandlerManager;
use Hub\Environment\EnvironmentInterface;
use Hub\Workspace\WorkspaceInterface;
use Hub\Workspace\StartupWorkspace;
use Hub\Filesystem\Filesystem;
use Hub\Process\ProcessFactoryInterface;
use Hub\Process\ProcessFactory;

/**
 * The DI Container for the application.
 *
 * @method bool hasApplication()
 * @method bool hasWorkspace()
 * @method bool hasInput()
 * @method bool hasOutput()
 * @method bool hasOutputStyle()
 * @method bool hasLogger()
 * @method bool hasHttp()
 * @method bool hasExceptionHandlerManager()
 * @method bool hasFilesystem()
 * @method bool hasProcessFactory()
 */
class Container
{
    /**
     * @var array $services
     */
    private $services = [];

    /**
     * @return Application
     */
    public function getApplication(): Application
    {
        if(!$this->hasApplication()){
            throw new Exceptions\ServiceNotFoundException("application");
        }

        return $this->services['application'];
    }

    /**
     * @param Application $application
     */
    public function setApplication(Application $application)
    {
        $this->services['application'] = $application;
    }

    /**
     * @param Kernel $kernel
     * @return Application
     */
    public function createApplication(Kernel $kernel)
    {
        return new Application($kernel);
    }

    /**
     * @return WorkspaceInterface
     */
    public function getWorkspace(): WorkspaceInterface
    {
        if(!$this->hasWorkspace()){
            throw new Exceptions\ServiceNotFoundException("workspace");
        }

        return $this->services['workspace'];
    }

    /**
     * @param EnvironmentInterface $environment
     * @return WorkspaceInterface
     */
    public function createStartupWorkspace(EnvironmentInterface $environment): WorkspaceInterface
    {
        return new StartupWorkspace(
            $environment,
            $this->getInput(),
            $this->getFilesystem()
        );
    }

    /**
     * @param WorkspaceInterface $workspace
     */
    public function setWorkspace(WorkspaceInterface $workspace)
    {
        $this->services['workspace'] = $workspace;
    }

    /**
     * @return Console\Input\InputInterface
     */
    public function getInput(): Console\Input\InputInterface
    {
        return $this->services['input']
            ?? $this->services['input'] = new Console\Input\ArgvInput();
    }

    /**
     * @param Console\Input\InputInterface $input
     */
    public function setInput(Console\Input\InputInterface $input)
    {
        $this->services['input'] = $input;
    }

    /**
     * @return Console\Output\OutputInterface
     */
    public function getOutput(): Console\Output\OutputInterface
    {
        if(!$this->hasOutput()){
            throw new Exceptions\ServiceNotFoundException("output");
        }

        return $this->services['output'];
    }

    /**
     * @param EnvironmentInterface $environment
     * @return Console\Output\OutputInterface
     */
    public function createStartupOutput(EnvironmentInterface $environment): Console\Output\OutputInterface
    {
        return new Console\Output\ConsoleOutput(
            $environment->isDevelopment()
                ? Console\Output\OutputInterface::VERBOSITY_VERBOSE
                : Console\Output\OutputInterface::VERBOSITY_NORMAL,
            null,
            new OutputFormatter()
        );
    }

    /**
     * @param Console\Output\OutputInterface $output
     */
    public function setOutput(Console\Output\OutputInterface $output)
    {
        $this->services['output'] = $output;
    }

    /**
     * @return LoggerManagerInterface
     */
    public function getLogger(): LoggerManagerInterface
    {
        return $this->services['logger']
            ?? $this->services['logger'] = new LoggerManager();
    }

    /**
     * @param LoggerManagerInterface $logger
     */
    public function setLogger(LoggerManagerInterface $logger)
    {
        $this->services['logger'] = $logger;
    }

    /**
     * Sets the manager and Make sure that the handler is registered
     *
     * @return ExceptionHandlerManagerInterface
     */
    public function getExceptionHandlerManager(): ExceptionHandlerManagerInterface
    {
        return $this->services['exceptionHandlerManager']
            ?? $this->services['exceptionHandlerManager'] = ExceptionHandlerManager::getInstance()->register();
    }

    /**
     * @param ExceptionHandlerManagerInterface $exceptionHandlerManager
     */
    public function setExceptionHandlerManager(ExceptionHandlerManagerInterface $exceptionHandlerManager)
    {
        $this->services['exceptionHandlerManager'] = $exceptionHandlerManager->register();
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->services['filesystem']
            ?? $this->services['filesystem'] = new Filesystem();
    }

    /**
     * @param Filesystem $filesystem
     */
    public function setFilesystem(Filesystem $filesystem)
    {
        $this->services['filesystem'] = $filesystem;
    }

    /**
     * @return HttpMethodsClient
     */
    public function getHttp(): HttpMethodsClient
    {
        return $this->services['http']
            ?? $this->services['http'] = $this->createHttpMethodsClient(new Guzzle6\Client());
    }

    /**
     * @param HttpClient $http
     */
    public function setHttp(HttpClient $http)
    {
        $this->services['http'] = $this->createHttpMethodsClient($http);
    }

    /**
     * @param HttpClient $http
     * @return HttpMethodsClient
     */
    public function createHttpMethodsClient(HttpClient $http): HttpMethodsClient
    {
        if($http instanceof HttpMethodsClient){
            return $http;
        }

        return new HttpMethodsClient($http, new GuzzleMessageFactory());
    }

    /**
     * @return Console\Style\StyleInterface
     */
    public function getOutputStyle(): Console\Style\StyleInterface
    {
        return $this->services['outputStyle']
            ?? $this->services['outputStyle'] = new Console\Style\SymfonyStyle($this->getInput(), $this->getOutput());
    }

    /**
     * @param Console\Style\StyleInterface $style
     */
    public function setOutputStyle(Console\Style\StyleInterface $style)
    {
        $this->services['outputStyle'] = $style;
    }

    /**
     * @return ProcessFactoryInterface
     */
    public function getProcessFactory(): ProcessFactoryInterface
    {
        return $this->services['processFactory']
            ?? $this->services['processFactory'] = new ProcessFactory($this->getLogger());
    }

    /**
     * @param ProcessFactoryInterface $facory
     */
    public function setProcessFactory(ProcessFactoryInterface $facory)
    {
        $this->services['processFactory'] = $facory;
    }

    /**
     * Handles has(Service) magic methods.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if('has' === strtolower(substr($name, 0, 3))){
            $service = lcfirst(substr($name, 3));
            return isset($this->services[$service])
                ? true
                : false;
        }

        throw new \BadMethodCallException('Call to undefined method '.__CLASS__.'::'.$name.'()');
    }
}
