<?php

$header = <<<'EOF'
    This file is part of AwesomeHub CLI.

    (c) Mohamed Elkholy <mohatt@pm.me>

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
        '@PHP80Migration' => true,
        '@PHP80Migration:risky' => true,
        //'header_comment' => ['comment_type' => 'comment', 'header' => $header],
    ])
    ->setFinder(PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->notPath([
        'Hub/EntryList/EntryListDefinition.php',
        'Hub/Workspace/Config/WorkspaceConfigDefinition.php',
    ])
    ->in(__DIR__))
    ;
