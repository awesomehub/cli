<?php
namespace Hub\Command;

use Symfony\Component\Console\Command\ListCommand;

/**
 * Renames the default 'list' command to 'commands'.
 *
 * @package AwesomeHub
 */
class CommandsCommand extends ListCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('commands');
    }
}