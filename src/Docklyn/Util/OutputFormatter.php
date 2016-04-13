<?php
namespace Docklyn\Util;

use Symfony\Component\Console\Formatter\OutputFormatter as BaseFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class OutputFormatter extends BaseFormatter
{
    /**
     * {@inheritdoc}
     */
    public function __construct($decorated = false, array $styles = array())
    {
        $styles = array_merge([
            'error'     => new OutputFormatterStyle('red', null, ['bold']),
            'info'      => new OutputFormatterStyle('green'),
            'comment'   => new OutputFormatterStyle('yellow'),
            'question'  => new OutputFormatterStyle('black', 'cyan'),

            // Extra style tags
            'danger'    => new OutputFormatterStyle('yellow', 'red', ['bold']),
            'warning'   => new OutputFormatterStyle('yellow', null, ['bold']),
            'notice'    => new OutputFormatterStyle('green', null, ['bold']),
            'debug'     => new OutputFormatterStyle('cyan'),
        ], $styles);

        parent::__construct($decorated, $styles);
    }
}
