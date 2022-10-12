<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

return (new \PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules(
        [
            // rule sets
            '@DoctrineAnnotation' => true,
            '@PHP56Migration:risky' => true,
            '@PHP70Migration' => true,
            '@PHP70Migration:risky' => true,
            '@PHP71Migration' => true,
            '@PHP71Migration:risky' => true,
            '@PHPUnit57Migration:risky' => true,
            '@PHPUnit60Migration:risky' => true,
            '@PHPUnit75Migration:risky' => true,
            '@PHPUnit84Migration:risky' => true,
            '@PER' => true,

            // alias
            'no_alias_functions' => true,

            // array notation
            'array_syntax' => ['syntax' => 'short'],
            'no_trailing_comma_in_singleline_array' => true,
            'no_whitespace_before_comma_in_array' => true,
            'whitespace_after_comma_in_array' => true,

            // basic
            'encoding' => true,
            'psr_autoloading' => true,

            // casing
            'magic_constant_casing' => true,
            'native_function_casing' => true,

            // cast notation
            'cast_spaces' => ['space' => 'none'],
            'lowercase_cast' => true,
            'modernize_types_casting' => true,
            'no_short_bool_cast' => true,
            'short_scalar_cast' => true,

            // class notation
            'class_attributes_separation' => true,
            'no_blank_lines_after_class_opening' => true,
            'no_php4_constructor' => true,

            // class usage
            // (no rules used from this section)

            // comment
            'no_empty_comment' => true,
            'single_line_comment_style' => true,

            // constant notation
            // (no rules used from this section)

            // control structure
            'elseif' => true,
            'no_superfluous_elseif' => true,
            'no_unneeded_control_parentheses' => true,
            'no_unneeded_curly_braces' => true,
            'no_useless_else' => true,
            'trailing_comma_in_multiline' => true,
            'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],

            // function notation
            'function_typehint_space' => true,
            'native_function_invocation' => ['include' => ['@all']],
            'no_spaces_after_function_name' => true,
            'return_type_declaration' => ['space_before' => 'none'],

            // import
            'no_leading_import_slash' => true,
            'no_unused_imports' => true,
            'ordered_imports' => true,

            // language construct
            'combine_consecutive_issets' => true,
            'combine_consecutive_unsets' => true,
            'declare_equal_normalize' => true,
            'dir_constant' => true,
            'is_null' => true,

            // list notation
            // (no rules used from this section)

            // namespace notation
            'no_leading_namespace_whitespace' => true,

            // naming
            // (no rules used from this section)

            // operator
            'concat_space' => ['spacing' => 'one'],
            'new_with_braces' => true,
            'standardize_not_equals' => true,
            'ternary_operator_spaces' => true,
            'ternary_to_null_coalescing' => true,
            'unary_operator_spaces' => true,

            // PHP tag
            'blank_line_after_opening_tag' => true,
            'echo_tag_syntax' => true,
            'linebreak_after_opening_tag' => true,

            // PHPUnit
            'php_unit_construct' => true,
            'php_unit_fqcn_annotation' => true,
            'php_unit_set_up_tear_down_visibility' => true,
            'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],

            // PHPDoc
            'no_blank_lines_after_phpdoc' => true,
            'no_empty_phpdoc' => true,
            'phpdoc_add_missing_param_annotation' => true,
            'phpdoc_indent' => true,
            'phpdoc_no_package' => true,
            'phpdoc_scalar' => true,
            'phpdoc_separation' => true,
            'phpdoc_trim' => true,
            'phpdoc_types' => true,
            'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],

            // return notation
            'no_useless_return' => true,

            // semicolon
            'multiline_whitespace_before_semicolons' => true,
            'no_empty_statement' => true,
            'no_singleline_whitespace_before_semicolons' => true,
            'semicolon_after_instruction' => true,
            'space_after_semicolon' => true,

            // strict
            'declare_strict_types' => true,

            // string notation
            'escape_implicit_backslashes' => ['single_quoted' => true],
            'single_quote' => true,

            // whitespace
            'compact_nullable_typehint' => true,
            'no_extra_blank_lines' => true,
            'no_spaces_inside_parenthesis' => true,
            'no_whitespace_in_blank_line' => true,
        ]
    );
