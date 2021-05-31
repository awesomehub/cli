<?php

declare(strict_types=1);

namespace Hub;

use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The main console application class.
 */
class Application extends Console\Application
{
    public const NAME = 'AwesomeHub';
    public const SLUG = 'awesomeHub';
    public const VERSION = '0.1.0';

    public function __construct(protected KernelInterface $kernel)
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->setDefaultCommand('commands');
    }

    /**
     * {@inheritdoc}
     */
    public function run(Input\InputInterface $input = null, Output\OutputInterface $output = null): int
    {
        $container = $this->getContainer();

        // Prevent symfony from catching exceptions if an exception handler manager has been registered
        if ($container->has('exception')) {
            $this->setCatchExceptions(false);
        }

        if (null === $input) {
            $input = $container->get('input');
        }

        if (null === $output) {
            $output = $container->get('output');
        }

        return parent::run($input, $output);
    }

    /**
     * Gets the DI Container instance.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->kernel->getContainer();
    }

    /**
     * Gets the Kernel instance.
     */
    public function getKernel(): KernelInterface
    {
        return $this->kernel;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands(): array
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
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition(): Input\InputDefinition
    {
        return new Input\InputDefinition([
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
        ]);
    }
}
