<?php
namespace Hub;

use Psr\Log\LogLevel;
use Hub\Environment\EnvironmentInterface;
use Hub\Environment\Environment;
use Hub\Exception\Handler\StartupExceptionHandler;
use Hub\Exception\Handler\LoggerExceptionHandler;
use Hub\Logger\Handler\ConsoleLoggerHandler;
use Hub\Logger\Handler\StreamLoggerHandler;

/**
 * The App Kernel.
 *
 * @package AwesomeHub
 */
class Kernel implements KernelInterface
{
    /**
     * @var Container $container
     */
    protected $container;

    /**
     * @var EnvironmentInterface $environment
     */
    protected $environment;

    /**
     * @var bool $booted
     */
    protected $booted = false;

    /**
     * Kernel constructor.
     *
     * @param EnvironmentInterface $environment
     * @param Container $container
     */
    public function __construct(EnvironmentInterface $environment = null, Container $container = null)
    {
        $this->environment = $environment ?: new Environment();
        $this->container = $container ?: new Container();
    }

    /**
     * Kernel destructor.
     */
    public function __destruct()
    {
        $this->shutdown();
    }

    /**
     * @inheritdoc
     */
    public function boot()
    {
        // Check it it's already booted up
        if ($this->isBooted()) {
            return;
        }

        // First, we need to ensure we have a valid workspace
        $this->container->setWorkspace(
            $this->container->createStartupWorkspace($this->environment)
        );

        // Ensure we have a valid output
        $this->container->setOutput(
            $this->container->createStartupOutput($this->environment)
        );

        // Ensure we have a valid logger
        $loggerManager = $this->container->getLogger();
        $loggers = $loggerManager->getHandlers();
        if(count($loggers) == 0){
            $loggerManager->setHandlers([
                new ConsoleLoggerHandler($this->container->getOutput()),
                new StreamLoggerHandler($this->container->getWorkspace()->path('debug.log'), LogLevel::DEBUG),
                new StreamLoggerHandler($this->container->getWorkspace()->path('error.log'), LogLevel::WARNING),
            ]);
        }

        // Ensure we have a valid exception handler
        $manager = $this->container->getExceptionHandlerManager();
        $handlers = $manager->getHandlers();
        if(empty($handlers) ||
            (count($handlers) == 1 &&
                current($handlers) instanceof StartupExceptionHandler)
        ){
            $manager->setHandlers([
                new LoggerExceptionHandler($this->container->getLogger())
            ]);
        }

        // Create our actual application
        $application = $this->container->createApplication($this);

        // Run our application
        $application->run();

        $this->booted = true;
    }

    /**
     * @inheritdoc
     */
    public function shutdown()
    {
        // Chck if we are not booted
        if (!$this->isBooted()) {
            return;
        }

        $this->booted = false;
    }

    /**
     * @inheritdoc
     */
    public function isBooted()
    {
        return true === $this->booted;
    }

    /**
     * @inheritdoc
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @inheritdoc
     */
    public function getContainer()
    {
        return $this->container;
    }
}
