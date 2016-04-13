<?php
namespace Docklyn\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends BaseCommand
{
    /**
     * @var \Docklyn\Docklyn $docklyn
     */
    protected $docklyn;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->docklyn = $this->getApplication()->getDocklyn();
        $this->logger = $this->docklyn->getLogger();

        return parent::run($input, $output);
    }

    /**
     * Gets the application instance for this command.
     *
     * @return \Docklyn\Application An Application instance
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}