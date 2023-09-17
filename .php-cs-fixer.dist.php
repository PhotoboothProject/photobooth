<?php

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'general_phpdoc_annotation_remove' => [
            'annotations' => [
                'author'
            ]
        ],
        'no_trailing_comma_in_singleline' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_unused_imports' => true,
        'concat_space' => ['spacing' => 'one'],
        'single_quote' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'no_blank_lines_after_phpdoc' => true,
        'array_syntax' => ['syntax' => 'short'],
        'whitespace_after_comma_in_array' => true,
        'type_declaration_spaces' => true,
        'single_line_comment_style' => true,
        'no_alias_functions' => true,
        'no_leading_namespace_whitespace' => true,
        'native_function_casing' => true,
        'self_accessor' => true,
        'no_short_bool_cast' => true,
        'no_unneeded_control_parentheses' => true
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('bin')
            ->exclude('node_modules')
            ->exclude('tools')
            ->exclude('vendor')
            ->in(__DIR__)
    );