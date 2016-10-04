<?php
namespace Hub\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hub\Process\ProcessFactory;
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
     * @var ProcessFactory $process
     */
    protected $process;

    /**
     * @var Filesystem $filesystem
     */
    protected $filesystem;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @inheritdoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->container = $this->getApplication()->getContainer();

        $this->logger = $this->container->getLogger();
        $this->process = $this->container->getProcessFactory();
        $this->filesystem = $this->container->getFilesystem();

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