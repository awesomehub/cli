<?php
namespace Hub\Command;

use Symfony\Component\Console;
use Psr\Log\LoggerInterface;
use Hub\Environment\EnvironmentInterface;
use Hub\Workspace\WorkspaceInterface;
use Hub\Process\ProcessFactoryInterface;
use Hub\Filesystem\Filesystem;
use Hub\Application;
use Hub\Container;

/**
 * Base command abstract class.
 *
 * @package AwesomeHub
 */
abstract class Command extends Console\Command\Command
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
     * @var Filesystem $filesystem
     */
    protected $filesystem;

    /**
     * @var Console\Input\InputInterface $input
     */
    protected $input;

    /**
     * @var Console\Output\OutputInterface $output
     */
    protected $output;

    /**
     * @var Console\Style\StyleInterface $output
     */
    protected $style;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var ProcessFactoryInterface $process
     */
    protected $process;

    /**
     * @inheritdoc
     */
    public function run(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $this->container    = $this->getApplication()->getContainer();

        $this->environment  = $this->getApplication()->getKernel()->getEnvironment();
        $this->filesystem   = $this->container->getFilesystem();
        $this->workspace    = $this->container->getWorkspace();
        $this->input        = $this->container->getInput();
        $this->output       = $this->container->getOutput();
        $this->style        = $this->container->getOutputStyle();
        $this->logger       = $this->container->getLogger();
        $this->process      = $this->container->getProcessFactory();

        return parent::run($input, $output);
    }

    /**
     * Gets the application instance for this command.
     *
     * @return Application|Console\Application
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}