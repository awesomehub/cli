<?php
namespace Hub;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\HelpCommand;
use Hub\Exception\ExceptionHandlerManagerInterface;

/**
 * The main console application class.
 *
 * @package AwesomeHub
 */
class Application extends BaseApplication
{
    const NAME    = 'AwesomeHub';
    const SLUG    = 'awesomeHub';
    const VERSION = '0.1.0';

    /**
     * @var Container $container
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->setDefaultCommand('commands');
        $this->container = $container;
    }

    /**
     * Runs the application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     * @return int 0 if everything went fine, or an error code
     */
    public function run(InputInterface $input = null,  OutputInterface $output = null)
    {
        $exceptionHandler = $this->container->getExceptionHandler();
        // Prevent symfony from catching exceptions if an exception handler manager has been registered
        if($exceptionHandler instanceof ExceptionHandlerManagerInterface){
            $this->setCatchExceptions(false);
        }

        if(!$input)
            $input = $this->container->getInput();

        if(!$output)
            $output = $this->container->getOutput();

        return parent::run($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultCommands()
    {
        return [
            new HelpCommand(),
            new Command\CommandsCommand()
        ];
    }

    /**
     * Gets DI Container instance.
     *
     * @param void
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(array(
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('--workspace', '-w', InputOption::VALUE_REQUIRED, 'Sets the workspace directory'),
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
