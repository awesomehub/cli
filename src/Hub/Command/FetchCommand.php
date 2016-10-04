<?php
namespace Hub\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command class for 'fetch' command
 * 
 * @package AwesomeHub
 */
class FetchCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fetch')
            ->setDescription('Fetches a hub list using a specified list definition file.')
            ->addArgument(
                'list',
                InputArgument::REQUIRED,
                'The name of the list definition file'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmd = $input->getArgument('list');

        var_dump($cmd);

    }
}
