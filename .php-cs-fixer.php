<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
return $config->setFinder($finder)
    ->setRules([
        '@PSR2' => true,
        '@PHP80Migration' => true,
        '@PhpCsFixer' => true,
        '@Symfony' => false,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_whitespace_before_comma_in_array' => true,
        'braces' => [
            'allow_single_line_anonymous_class_with_empty_body' => true,
            'allow_single_line_closure' => true,
            'position_after_functions_and_oop_constructs' => 'same',
        ],
        'no_blank_lines_after_class_opening' => false,
        'class_reference_name_casing' => true,
        'cast_spaces' => true,
        'class_attributes_separation' => ['elements' => ['property' => 'only_if_meta']],
        'no_empty_comment' => true,
        'no_trailing_whitespace_in_comment' => true,
        'single_line_comment_style' => true,
        'include' => true,
        'no_trailing_comma_in_list_call' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_curly_braces' => true,
        'simplified_if_return' => true,
        'switch_continue_to_break' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => true,
        'no_unneeded_import_alias' => true,
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'no_leading_namespace_whitespace' => true,
        'object_operator_without_whitespace' => true,
        'simplified_null_return' => true,
        'no_empty_statement' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'compact_nullable_typehint' => true,
        'no_extra_blank_lines' => true,
    ]);
