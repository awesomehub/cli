<?php

$header = <<<'EOF'
This file is part of AwesomeHub CLI.

(c) Mohamed Kholy <mkh117@gmail.com>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

//Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

return Symfony\CS\Config::create()
    ->setUsingCache(true)
    // use default SYMFONY_LEVEL and extra fixers:
    ->fixers([
        'header_comment',
        'short_array_syntax',
        'no_useless_else',
        'no_useless_return',
        'phpdoc_order',
        'align_equals',
        'align_double_arrow',
    ])
    ->finder(
        Symfony\CS\Finder::create()
            ->in(__DIR__.'/src')
    )
    ;
