<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/example',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,
    ])
    ->setFinder($finder);
