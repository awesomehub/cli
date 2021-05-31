<?php

namespace Hub;

use Hub\Environment\Environment;
use Hub\Environment\EnvironmentInterface;
use Hub\Exception\ExceptionHandlerPass;
use Hub\Logger\LoggerHandlerPass;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * The App Kernel.
 */
abstract class Kernel implements KernelInterface
{
    protected ContainerInterface $container;
    protected Environment | EnvironmentInterface $environment;
    protected bool $booted = false;

    /**
     * Kernel constructor.
     *
     * Possible values for the mode are:
     *  - EnvironmentInterface::DEVELOPMENT
     *  - EnvironmentInterface::PRODUCTION
     */
    public function __construct(EnvironmentInterface $environment = null, string $mode = null)
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
     * {@inheritdoc}
     */
    public function boot(): void
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
     * {@inheritdoc}
     */
    public function shutdown(): void
    {
        // Chck if we are not booted
        if (!$this->isBooted()) {
            return;
        }

        $this->booted = false;
    }

    /**
     * {@inheritdoc}
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment(): EnvironmentInterface
    {
        return $this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Loads the container configuration.
     */
    abstract protected function registerContainerConfiguration(LoaderInterface $loader): void;

    /**
     * Initializes the service container.
     *
     * The cached version of the service container is used when fresh, otherwise the
     * container is built.
     */
    protected function initializeContainer(): void
    {
        $class = 'CachedContainer';
        $cache = new ConfigCache(__DIR__.\DIRECTORY_SEPARATOR.$class.'.php', $this->environment->isDevelopment());
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
     */
    protected function buildContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->addObjectResource($this);
        $container->addObjectResource($this->environment);

        $this->registerContainerConfiguration($this->getContainerLoader($container));

        // These are just placeholders to allow the container to compile
        // without errors about non-existing services
        $container->register('kernel')->setSynthetic(true);
        $container->register('environment')->setSynthetic(true);

        $container->addCompilerPass(new LoggerHandlerPass());
        $container->addCompilerPass(new ExceptionHandlerPass());

        return $container;
    }

    /**
     * Dumps the service container to PHP code in the cache.
     *
     * @param ConfigCache      $cache     The config cache
     * @param ContainerBuilder $container The service container
     * @param string           $class     The name of the class to generate
     */
    protected function dumpContainer(ConfigCache $cache, ContainerBuilder $container, string $class): void
    {
        $dumper = new PhpDumper($container);
        $content = $dumper->dump([
            'class' => $class,
            'namespace' => __NAMESPACE__,
            'file' => $cache->getPath(),
            'debug' => $this->environment->isDevelopment(),
        ]);
        $cache->write($content, $container->getResources());
    }

    /**
     * Returns a loader for the service container.
     */
    protected function getContainerLoader(ContainerBuilder $container): DelegatingLoader
    {
        $locator = new FileLocator(\dirname(__DIR__));
        $resolver = new LoaderResolver([
            new XmlFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        return new DelegatingLoader($resolver);
    }
}
