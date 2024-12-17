<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__."/src")
    ->in(__DIR__."/tests");

return (new PhpCsFixer\Config())
    ->setRules([
        '@PHP81Migration' => true,
        '@PhpCsFixer' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
    ])
    ->setFinder($finder);
