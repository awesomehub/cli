<?php
namespace Hub\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Hub\EntryList\EntryListInterface;

/**
 * Inspects a fetched list.
 * 
 * @package AwesomeHub
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

        // Print process title
        $this->style->title('Inspecting List: ' . $list);

        // Fetch the cached list
        $cachedPath = $this->environment->getWorkspace()->path(['cache', 'lists', $list]);
        if(!file_exists($cachedPath)){
            throw new \LogicException("Unable to fined a cached list named '$list'. Maybe you need to 'list:fetch $list' first.");
        }

        // Set list instance
        $this->list = unserialize(file_get_contents($cachedPath));

        // Show basic info
        $this->style->section("Basic Information");
        $this->printInfo();

        // Show list categories
        $this->style->section("Categories Information");
        $this->printCategories();
    }

    /**
     * Prints list categories in hierarchical order.
     *
     * @param int $parent
     * @param int $depth
     * @return array|bool
     */
    protected function printCategories(int $parent = 0, int $depth = 0)
    {
        $body = [];
        foreach ($this->list->get('categories') as $id => $category){
            if($category['parent'] == $parent){
                $row = [ str_repeat('-', $depth+1) . ' ' . $category['title'] ];
                foreach ($category['count'] as $type => $count){
                    $row[] = $count;
                }
                $body[] = $row;
                $body = array_merge($body, $this->printCategories($id, $depth + 1));
            }
        }

        if($depth === 0){
            if(count($body) == 0){
                $this->style->text("No categories found");
                return true;
            }
            $header = ['Category'];
            foreach (current($this->list->get('categories'))['count'] as $type => $count){
                $header[] = $type;
            }

            $this->style->table($header, $body);
            return true;
        }

        return $body;
    }

    /**
     * Print list basic info.
     *
     * @return void
     */
    protected function printInfo()
    {
        $data = [
            'ID' => $this->list->get('id'),
            'Name' => $this->list->get('name'),
            'Processed' => $this->list->isProcessed() ? 'Yes' : 'No',
            'Resolved' => $this->list->isResolved() ? 'Yes' : 'No',
            'Sources' => count($this->list->get('sources')),
            'Categories' => count($this->list->get('categories')),
            'Entries' => count($this->list->get('entries')),
        ];

        $list = [];
        foreach ($data as $key => $value){
            $list[] = sprintf('<info>%1$s:</info> %2$s', $key, $value);
        }

        $this->style->listing($list);
    }
}
