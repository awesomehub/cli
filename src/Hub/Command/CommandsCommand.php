<?php

declare(strict_types=1);

namespace Hub\Command;

use Symfony\Component\Console\Command\ListCommand;

/**
 * Renames the default 'list' command to 'commands'.
 */
class CommandsCommand extends ListCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('commands');
    }
}
