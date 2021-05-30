<?php

namespace Hub\Command;

use Hub\Application;
use Hub\Environment\EnvironmentInterface;
use Hub\Filesystem\Filesystem;
use Hub\IO\IOInterface;
use Hub\Process\ProcessFactoryInterface;
use Hub\Workspace\WorkspaceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base command abstract class.
 */
abstract class Command extends BaseCommand
{
    protected ContainerInterface $container;
    protected EnvironmentInterface $environment;
    protected WorkspaceInterface $workspace;
    protected Filesystem $filesystem;
    protected InputInterface $input;
    protected OutputInterface $output;
    protected IOInterface $io;
    protected LoggerInterface $logger;
    protected ProcessFactoryInterface $process;

    /**
     * {@inheritdoc}
     */
    final public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->container = $this->getApplication()->getContainer();

        $this->environment = $this->getApplication()->getKernel()->getEnvironment();
        $this->filesystem = $this->container->get('filesystem');
        $this->workspace = $this->container->get('workspace');
        $this->input = $this->container->get('input');
        $this->output = $this->container->get('output');
        $this->io = $this->container->get('io');
        $this->logger = $this->container->get('logger');
        $this->process = $this->container->get('process.factory');

        return parent::run($input, $output);
    }

    /**
     * Gets the application instance for this command.
     */
    public function getApplication(): Application | \Symfony\Component\Console\Application
    {
        return parent::getApplication();
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->init();
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->validate();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->logger->debug(sprintf("Current command '%s'", implode(' ', $_SERVER['argv'])));
        $this->logger->debug(sprintf("Current workspace '%s'", $this->workspace->path()));

        return $this->exec();
    }

    /**
     * Initializes the command just after the input has been validated.
     */
    protected function init(): void
    {
    }

    /**
     * This is where the command can interactively ask for values of missing required arguments..
     */
    protected function validate(): void
    {
    }

    /**
     * Executes the current command.
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    abstract protected function exec(): ?int;
}
