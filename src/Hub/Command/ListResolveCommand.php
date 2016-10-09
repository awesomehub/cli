<?php
namespace Hub\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Hub\EntryList\EntryListInterface;

/**
 * Fetches and processes a given list.
 * 
 * @package AwesomeHub
 */
class ListResolveCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('list:resolve')
            ->setDescription('Resolves a fetched hub list.')
            ->addArgument(
                'list', InputArgument::REQUIRED, 'The name of the cached list'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = $input->getArgument('list');

        $cachedPath = $this->environment->getWorkspace()->get(['cache', 'lists', $list]);

        if(!file_exists($cachedPath)){
            throw new \LogicException("Unable to fined a cached list named '$list'. Maybe you need to 'list:fetch $list' first.");
        }

        /* @var EntryListInterface $list */
        $list = unserialize(file_get_contents($cachedPath));

        dump($list->isProcessed());
        dump($list->isResolved());
    }
}
