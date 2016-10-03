<?php
namespace Docklyn;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * The main console application class.
 *
 * @package Docklyn
 */
class Application extends BaseApplication
{
    /**
     * @var Docklyn $docklyn
     */
    protected $docklyn;

    /**
     * Constructor.
     *
     * @param Docklyn $docklyn
     */
    public function __construct(Docklyn $docklyn)
    {
        parent::__construct('Docklyn', Docklyn::VERSION);

        $this->docklyn = $docklyn;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultCommands()
    {
        return array_merge(parent::getDefaultCommands(), [
            new Command\Git\GitShellCommand(),
        ]);
    }

    /**
     * Gets Docklyn instance.
     *
     * @param void
     * @return Docklyn
     */
    public function getDocklyn()
    {
        return $this->docklyn;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(array(
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
            // we don't need the 3 verbosity level, only one level is enough
            new InputOption('--verbose', '-v', InputOption::VALUE_NONE, 'Increase the verbosity of messages'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
            new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
            new InputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
        ));
    }
}
