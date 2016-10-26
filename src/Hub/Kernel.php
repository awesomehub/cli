<?php
namespace Hub;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Hub\Environment\EnvironmentInterface;
use Hub\Environment\Environment;
use Hub\Exception\ExceptionHandlerPass;
use Hub\Logger\LoggerHandlerPass;

/**
 * The App Kernel.
 *
 * @package AwesomeHub
 */
abstract class Kernel implements KernelInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * Kernel constructor.
     *
     * @param EnvironmentInterface $environment
     * @param string $mode Possible values are EnvironmentInterface::DEVELOPMENT and EnvironmentInterface::PRODUCTION
     */
    public function __construct(EnvironmentInterface $environment = null, $mode = null)
    {
        $this->environment = $environment ?: new Environment($mode);
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

        // init container
        $this->initializeContainer();

        // Preload important services
        $this->container->get('exception');
        $this->container->get('workspace');
        $this->container->get('io');

        // Run our application
        $application = $this->container->get('application');
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

    /**
     * Initializes the service container.
     *
     * The cached version of the service container is used when fresh, otherwise the
     * container is built.
     */
    protected function initializeContainer()
    {
        $class = 'CachedContainer';
        $cache = new ConfigCache(__DIR__.DIRECTORY_SEPARATOR.$class.'.php', $this->environment->isDevelopment());
        if (!$cache->isFresh()) {
            $container = $this->buildContainer();
            $container->compile();
            $this->dumpContainer($cache, $container, $class);
        }

        $this->container = new CachedContainer();
        $this->container->set('kernel', $this);
        $this->container->set('environment', $this->environment);
    }

    /**
     * Creates the container builder.
     *
     * @return ContainerBuilder
     */
    protected function buildContainer()
    {
        $container = new ContainerBuilder();
        $container->addObjectResource($this);
        $container->addObjectResource($this->environment);

        if (null !== $cont = $this->registerContainerConfiguration($this->getContainerLoader($container))) {
            $container->merge($cont);
        }

        // These are just placeholders to allow the container to compile
        // without errors about non-existing services
        $container->set('kernel', new \stdClass());
        $container->set('environment', new \stdClass());

        $container->addCompilerPass(new LoggerHandlerPass($this));
        $container->addCompilerPass(new ExceptionHandlerPass($this));

        return $container;
    }

    /**
     * Dumps the service container to PHP code in the cache.
     *
     * @param ConfigCache      $cache     The config cache
     * @param ContainerBuilder $container The service container
     * @param string           $class     The name of the class to generate
     */
    protected function dumpContainer(ConfigCache $cache, ContainerBuilder $container, $class)
    {
        $dumper = new PhpDumper($container);
        $content = $dumper->dump([
            'class' => $class,
            'namespace' => __NAMESPACE__,
            'file' => $cache->getPath(),
            'debug' => $this->environment->isDevelopment()
        ]);
        $cache->write($content, $container->getResources());
    }

    /**
     * Returns a loader for the container.
     *
     * @param ContainerBuilder $container The service container
     *
     * @return DelegatingLoader The loader
     */
    protected function getContainerLoader(ContainerBuilder $container)
    {
        $locator = new FileLocator(dirname(__DIR__));
        $resolver = new LoaderResolver([
            new XmlFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);
        return new DelegatingLoader($resolver);
    }

    /**
     * Loads the container configuration.
     *
     * @param LoaderInterface $loader A LoaderInterface instance
     */
    abstract protected function registerContainerConfiguration(LoaderInterface $loader);
}
