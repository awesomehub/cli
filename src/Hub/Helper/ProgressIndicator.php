<?php

namespace Hub\Helper;

use Symfony\Component\Console;

class ProgressIndicator extends Console\Helper\ProgressIndicator
{
    public function clear()
    {
        $theif = \Closure::bind(function () {
            return [
                $this->{'output'},
                $this->{'lastMessagesLength'},
            ];
        }, $this, parent::class);

        // Steal private properties from parent
        // Sorry but I got no choise
        $stolen = $theif();

        /** @var Console\Output\OutputInterface $output */
        $output = $stolen[0];

        $output->write(str_repeat("\x08", $stolen[1]), false);
    }
}
