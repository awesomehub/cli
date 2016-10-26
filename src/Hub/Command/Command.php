<?php

namespace Hub\Command;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console;
use Psr\Log\LoggerInterface;
use Hub\IO\IOInterface;
use Hub\Environment\EnvironmentInterface;
use Hub\Workspace\WorkspaceInterface;
use Hub\Process\ProcessFactoryInterface;
use Hub\Filesystem\Filesystem;
use Hub\Application;

/**
 * Base command abstract class.
 *
 * @package AwesomeHub
 */
abstract class Command extends Console\Command\Command
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
     * @var WorkspaceInterface
     */
    protected $workspace;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var IOInterface $io
     */
    protected $io;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ProcessFactoryInterface
     */
    protected $process;

    /**
     * @inheritdoc
     */
    final public function run(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $this->container    = $this->getApplication()->getContainer();

        $this->environment  = $this->getApplication()->getKernel()->getEnvironment();
        $this->filesystem   = $this->container->get('filesystem');
        $this->workspace    = $this->container->get('workspace');
        $this->input        = $this->container->get('input');
        $this->output       = $this->container->get('output');
        $this->io           = $this->container->get('io');
        $this->logger       = $this->container->get('logger');
        $this->process      = $this->container->get('process.factory');

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

    /**
     * @inheritdoc
     */
    protected function initialize(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        return $this->init();
    }

    /**
     * @inheritdoc
     */
    protected function interact(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        return $this->validate();
    }

    /**
     * @inheritdoc
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        return $this->exec();
    }

    /**
     * Initializes the command just after the input has been validated.
     */
    protected function init()
    {
    }

    /**
     * This is where the command can interactively ask for values of missing required arguments..
     */
    protected function validate()
    {
    }

    /**
     * Executes the current command.
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    abstract protected function exec();
}