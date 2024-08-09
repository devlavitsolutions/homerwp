<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    '@PhpCsFixer' => true,
    'ordered_class_elements' => ['sort_algorithm' => 'alpha'],
    'not_operator_with_space' => true,
    'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
];

$finder = Finder::create()->in([
    __DIR__ . '/app',
    __DIR__ . '/config',
    __DIR__ . '/database',
    __DIR__ . '/resources',
    __DIR__ . '/routes',
    __DIR__ . '/tests',
])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new Config();

return $config
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);