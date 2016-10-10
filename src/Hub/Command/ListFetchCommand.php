<?php
namespace Hub\Command;

use Hub\EntryList\SourceProcessor\UrlsSourceProcessor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Hub\EntryList\EntryListJson;
use Hub\Entry\Factory\UrlEntryFactory;
use Hub\Entry\Factory\TypeEntryFactory;
use Hub\Entry\Factory\UrlProcessor\GithubUrlProcessor;
use Hub\Entry\GithubRepoEntry;
use Hub\EntryList\SourceProcessor\EntriesSourceProcessor;
use Hub\EntryList\SourceProcessor\GithubMarkdownSourceProcessor;
use Hub\Filesystem\FilesystemUtil;

/**
 * Fetches and processes a given list.
 * 
 * @package AwesomeHub
 */
class ListFetchCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('list:fetch')
            ->setDescription('Fetches a hub list using a given list definition file.')
            ->addArgument(
                'list', InputArgument::REQUIRED, 'The name or path to the list definition file'
            )
            ->addOption(
                '--format', '-f', InputOption::VALUE_REQUIRED, 'The list file format (json or yaml)', 'json'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('list');
        $format = strtolower($input->getParameterOption('--format', 'json', true));

        // Print process title
        $this->style->title('Fetching List: ' . $path);

        // Check it it's relative path
        if(FilesystemUtil::isRelativePath($path)){
            $path = $this->environment->getWorkspace()->get(['lists', $path]);
        }

        // Add $format extension if not present
        if(!FilesystemUtil::hasExtension($path, $format)){
            $path .= '.' . $format;
        }

        // Create the list instancec
        $this->logger->info("Fetching list from '$path'");
        switch ($format){
            case 'json':
                $list = new EntryListJson($path);
                break;
            case 'yaml':
                throw new \LogicException("Yaml list format is not currently implemented.");
                break;
            default:
                throw new \LogicException("Unsupported list format provided '$format'.");
        }

        // Create needed entry factories
        $entryFromUrlFactory = new UrlEntryFactory([
            new GithubUrlProcessor()
        ]);
        $entryFromTypeFactory = new TypeEntryFactory([
            GithubRepoEntry::class
        ]);

        // Do the actual processing
        $list->process($this->logger, [
            new GithubMarkdownSourceProcessor($entryFromUrlFactory),
            new UrlsSourceProcessor($entryFromUrlFactory),
            new EntriesSourceProcessor($entryFromTypeFactory)
        ]);

        // Write serialized list to be resolved later
        $this->logger->info("Writing list cache file");
        $cachedPath = $this->environment->getWorkspace()->get(['cache', 'lists', basename($path, '.' . $format)]);
        if(false === file_put_contents($cachedPath, serialize($list))){
            throw new \RuntimeException("Failed writing list cache file to '$cachedPath'.");
        }

        // We're done
        $this->logger->info("Done!");
    }
}
