<?php
namespace Hub;

use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The main console application class.
 *
 * @package AwesomeHub
 */
class Application extends Console\Application
{
    const NAME    = 'AwesomeHub';
    const SLUG    = 'awesomeHub';
    const VERSION = '0.1.0';

    /**
     * @var KernelInterface $kernel
     */
    protected $kernel;

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->setDefaultCommand('commands');
        $this->kernel = $kernel;
    }

    /**
     * @inheritdoc
     */
    public function run(Input\InputInterface $input = null,  Output\OutputInterface $output = null)
    {
        $container = $this->getContainer();

        // Prevent symfony from catching exceptions if an exception handler manager has been registered
        if($container->has('exception')){
            $this->setCatchExceptions(false);
        }

        if(!$input)
            $input = $container->get('input');

        if(!$output)
            $output = $container->get('output');

        return parent::run($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultCommands()
    {
        return [
            new Console\Command\HelpCommand(),
            new Command\CommandsCommand(),
            new Command\MakeBuildCommand(),
            new Command\MakeCleanCommand(),
            new Command\ListBuildCommand(),
            new Command\ListInspectCommand(),
            new Command\GithubInspectCommand(),
            new Command\GithubTokensCommand(),
        ];
    }

    /**
     * Gets the DI Container instance.
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->kernel->getContainer();
    }

    /**
     * Gets the Kernel instance.
     *
     * @return KernelInterface
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition()
    {
        return new Input\InputDefinition(array(
            new Input\InputArgument('command', Input\InputArgument::REQUIRED, 'The command to execute'),

            new Input\InputOption('--workspace', '-w', Input\InputOption::VALUE_REQUIRED, 'Sets the workspace directory'),
            new Input\InputOption('--help', '-h', Input\InputOption::VALUE_NONE, 'Display this help message'),
            new Input\InputOption('--quiet', '-q', Input\InputOption::VALUE_NONE, 'Do not output any message'),
            // we don't need the 3 verbosity level, only one level is enough
            new Input\InputOption('--verbose', '-v', Input\InputOption::VALUE_NONE, 'Increase the verbosity of messages'),
            new Input\InputOption('--version', '-V', Input\InputOption::VALUE_NONE, 'Display this application version'),
            new Input\InputOption('--ansi', '', Input\InputOption::VALUE_NONE, 'Force ANSI output'),
            new Input\InputOption('--no-ansi', '', Input\InputOption::VALUE_NONE, 'Disable ANSI output'),
            new Input\InputOption('--no-interaction', '-n', Input\InputOption::VALUE_NONE, 'Do not ask any interactive question'),
        ));
    }
}
