<?php
namespace Hub\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hub\Environment\EnvironmentInterface;
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
     * @var InputInterface $input
     */
    protected $input;

    /**
     * @var OutputInterface $output
     */
    protected $output;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

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
        $this->input        = $this->container->getInput();
        $this->output       = $this->container->getOutput();
        $this->logger       = $this->container->getLogger();
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