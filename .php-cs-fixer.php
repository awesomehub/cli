<?php

$header = <<<'EOF'
    This file is part of AwesomeHub CLI.

    (c) Mohamed Kholy <mohatt@pm.me>

    This source file is subject to the MIT license that is bundled
    with this source code in the file LICENSE.
    EOF;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setIndent('    ')
    ->setRules([
        '@Symfony' => true,
        '@PhpCsFixer' => true,
        '@Symfony:risky' => true,
        '@PHP73Migration' => true,
        //'header_comment' => ['comment_type' => 'comment', 'header' => $header],
    ])
    ->setFinder(PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__))
    ;
