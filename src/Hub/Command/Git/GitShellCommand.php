<?php
namespace Hub\Command\Git;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command class for 'git:shell' command
 * 
 * @package AwesomeHub
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
                'The full command line that need be passed to git-shell'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmd = $input->getArgument('git-command');

        $pf = $this->getApplication()->getContainer()->getProcessFactory();
        $process = $pf->run('lsb_release -a');
        var_dump($process->getOutput());
        var_dump($cmd);

        $this->logger->critical('critical message');
        $this->logger->emergency('emergency message');
        $this->logger->alert('alert message');
        $this->logger->error('error message');
        $this->logger->warning('warning message');
        $this->logger->info('info message');
        $this->logger->notice('notice message');
        $this->logger->debug('debug message');

    }
}
