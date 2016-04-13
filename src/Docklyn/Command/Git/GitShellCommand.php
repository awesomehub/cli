<?php
namespace Docklyn\Command\Git;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command class for 'git:shell' command
 * 
 * @package Docklyn
 */
class GitShellCommand extends GitCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('git:shell')
            ->setDescription('Receives all incoming git-* commands and processes them. This command shouldn\'t be called directly.')
            ->addArgument(
                'git-command',
                InputArgument::REQUIRED,
                'The command that need be passed to git-shell'
            )
            ->addArgument(
                'git-command-args',
                InputArgument::IS_ARRAY,
                'The command arguments'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmd = $input->getArgument('git-command');
        $args = $input->getArgument('git-command-args');

        //@todo implement this

    }
}
