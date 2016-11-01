<?php

namespace Hub\Command;

use Hub\Entry\EntryInterface;
use Hub\EntryList\EntryListFile;
use Symfony\Component\Console\Input;
use Hub\EntryList\EntryListInterface;

/**
 * Inspects a fetched list.
 */
class ListInspectCommand extends Command
{
    /**
     * @var EntryListInterface
     */
    protected $list;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('list:inspect')
            ->setDescription('Inspects a fetched hub list.')
            ->addArgument(
                'list', Input\InputArgument::REQUIRED, 'The name of the cached list'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function exec()
    {
        $name = $this->input->getArgument('list');

        try {
            $this->list = EntryListFile::createFromCache($this->filesystem, $this->workspace, $name);
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());

            return 1;
        }

        $this->io->title($name);

        // Show basic info
        $this->io->section('Basic Information');
        $this->printInfo();

        // Show list categories
        $this->io->section('Categories Information');
        $this->printCategories();

        return 0;
    }

    /**
     * Prints list categories in hierarchical order.
     *
     * @param int $parent
     * @param int $depth
     *
     * @return array|bool
     */
    protected function printCategories(int $parent = 0, int $depth = 0)
    {
        $body  = [];
        $total = 0;
        foreach ($this->list->get('categories') as $id => $category) {
            if ($category['parent'] == $parent) {
                $row = [str_repeat('-', $depth + 1).' '.$category['title']];
                if ($depth === 0) {
                    $total += $category['count']['all'];
                }
                foreach ($category['count'] as $type => $count) {
                    if ($type !== 'all') {
                        $realCount = 0;
                        /** @var EntryInterface $ntry */
                        foreach ($this->list->get('entries') as $ntry) {
                            if (in_array($category['id'], $ntry->get('categories'))) {
                                ++$realCount;
                            }
                        }
                        if ($count !== $realCount) {
                            $count = sprintf('%d <debug>(%d)</debug>', $count, $realCount);
                        }
                    }
                    $row[] = $count;
                }
                $body[] = $row;
                $body   = array_merge($body, $this->printCategories($id, $depth + 1));
            }
        }

        if ($depth === 0) {
            if (count($body) == 0) {
                $this->io->text('No categories found');

                return true;
            }

            $header = ['Category'];
            foreach (current($this->list->get('categories'))['count'] as $type => $count) {
                $header[] = $type;
            }

            $this->io->table($header, $body);

            $this->io->listing([
                sprintf('<info>Total Count:</info> %d', $total),
            ]);

            return true;
        }

        return $body;
    }

    /**
     * Print list basic info.
     */
    protected function printInfo()
    {
        $data = [
            'ID'         => $this->list->get('id'),
            'Name'       => $this->list->get('name'),
            'Sources'    => count($this->list->get('sources')),
            'Categories' => count($this->list->get('categories')),
            'Entries'    => count($this->list->get('entries')),
        ];

        $list = [];
        foreach ($data as $key => $value) {
            $list[] = sprintf('<info>%1$s:</info> %2$s', $key, $value);
        }

        $this->io->listing($list);
    }
}
