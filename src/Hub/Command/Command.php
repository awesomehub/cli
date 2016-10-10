<?php
namespace Hub\Command;

use Psr\Log\LoggerInterface;
use Http\Client\Common\HttpMethodsClient;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Hub\Environment\EnvironmentInterface;
use Hub\Workspace\WorkspaceInterface;
use Hub\Process\ProcessFactoryInterface;
use Hub\Filesystem\Filesystem;
use Hub\Container;

/**
 * Base command abstract class.
 *
 * @package AwesomeHub
 */
abstract class Command extends BaseCommand
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
     * @var WorkspaceInterface $workspace
     */
    protected $workspace;

    /**
     * @var InputInterface $input
     */
    protected $input;

    /**
     * @var OutputInterface $output
     */
    protected $output;

    /**
     * @var StyleInterface $output
     */
    protected $style;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var HttpMethodsClient $http
     */
    protected $http;

    /**
     * @var ProcessFactoryInterface $process
     */
    protected $process;

    /**
     * @var Filesystem $filesystem
     */
    protected $filesystem;

    /**
     * @inheritdoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->container    = $this->getApplication()->getContainer();

        $this->environment  = $this->container->getEnvironment();
        $this->workspace    = $this->container->getWorkspace();
        $this->input        = $this->container->getInput();
        $this->output       = $this->container->getOutput();
        $this->style        = $this->container->getStyle();
        $this->logger       = $this->container->getLogger();
        $this->http         = $this->container->getHttp();
        $this->process      = $this->container->getProcessFactory();
        $this->filesystem   = $this->container->getFilesystem();

        return parent::run($input, $output);
    }

    /**
     * Gets the application instance for this command.
     *
     * @return \Hub\Application|\Symfony\Component\Console\Application An Application instance
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}