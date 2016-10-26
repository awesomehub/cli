<?php

namespace Hub\IO\Output;

use Symfony\Component\Console\Formatter\OutputFormatter as BaseFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class OutputFormatter extends BaseFormatter
{
    /**
     * {@inheritdoc}
     */
    public function __construct($decorated = false, array $styles = [])
    {
        $styles = array_merge([
            'error'    => new OutputFormatterStyle('red', null, ['bold']),
            'info'     => new OutputFormatterStyle('green'),
            'comment'  => new OutputFormatterStyle('yellow'),
            'question' => new OutputFormatterStyle('black', 'cyan'),

            // Extra style tags
            'danger'  => new OutputFormatterStyle('red', null, ['bold']),
            'warning' => new OutputFormatterStyle('yellow', null, ['bold']),
            'notice'  => new OutputFormatterStyle('green'),
            'debug'   => new OutputFormatterStyle('cyan'),

            'error_label'   => new OutputFormatterStyle('white', 'red', ['bold']),
            'info_label'    => new OutputFormatterStyle('white', 'green', ['bold']),
            'danger_label'  => new OutputFormatterStyle('black', 'red', ['bold']),
            'warning_label' => new OutputFormatterStyle('white', 'yellow', ['bold']),
            'notice_label'  => new OutputFormatterStyle('white', 'green', ['bold']),
            'debug_label'   => new OutputFormatterStyle('white', 'cyan', ['bold']),
        ], $styles);

        parent::__construct($decorated, $styles);
    }
}
