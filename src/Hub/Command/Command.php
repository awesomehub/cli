<?php
namespace Hub\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends BaseCommand
{
    /**
     * @var \Hub\Container $container
     */
    protected $container;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->container = $this->getApplication()->getContainer();
        $this->logger = $this->container->getLogger();

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