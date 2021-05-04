<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Traits;

use PHPUnit\Framework\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * Adds common data providers to a test case for a `Constraint` which matches CSS.
 *
 * @mixin TestCase
 */
trait CssDataProviders
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function provideEquivalentCss(): array
    {
        $equivalentCssWithAtMediaRuleSelectorListAndPropertyDeclaration = [
            'unminified CSS with `@media` rule, selector list, and property declaration'
                => ['@media screen { html, body { color: green; } }'],
            'minified CSS with `@media` rule, selector list, and property declaration'
                => ['@media screen{html,body{color:green}}'],
            'CSS with `@media` rule, selector list, and property declaration, with extra spaces'
                => ['  @media  screen  {  html  ,  body  {  color  :  green  ;  }  }  '],
            'CSS with `@media` rule, selector list, and property declaration, with linefeeds'
                => ["\n@media\nscreen\n{\nhtml\n,\nbody\n{\ncolor\n:\ngreen\n;\n}\n}\n"],
            'CSS with `@media` rule, selector list, and property declaration, with Windows line endings'
                => ["\r\n@media\r\nscreen\r\n{\r\nhtml\r\n,\r\nbody\r\n{\r\ncolor\r\n:\r\ngreen\r\n;\r\n}\r\n}\r\n"],
        ];

        /** @var array<string, array{0: string, 1: string}> $datasetsWithAtMediaRuleSelectorListAndPropertyDeclaration */
        $datasetsWithAtMediaRuleSelectorListAndPropertyDeclaration = DataProviders::cross(
            $equivalentCssWithAtMediaRuleSelectorListAndPropertyDeclaration,
            $equivalentCssWithAtMediaRuleSelectorListAndPropertyDeclaration
        );

        $equivalentCssWithAtImportRule = [
            '`@import` with unquoted string' => ['@import foo/bar.css;'],
            '`@import` with single-quoted string' => ['@import \'foo/bar.css\';'],
            '`@import` with double-quoted string' => ['@import "foo/bar.css";'],
            '`@import` with unquoted URL' => ['@import url(foo/bar.css);'],
            '`@import` with single-quoted URL' => ['@import url(\'foo/bar.css\');'],
            '`@import` with double-quoted URL' => ['@import url("foo/bar.css");'],
            '`@import` with spaces around unquoted URL' => ['@import url( foo/bar.css );'],
            '`@import` with spaces around quoted URL' => ['@import url( "foo/bar.css" );'],
            '`@import` with space after unquoted string' => ['@import foo/bar.css ;'],
            '`@import` with space after quoted string' => ['@import "foo/bar.css" ;'],
            '`@import` with space after URL' => ['@import url(foo/bar.css) ;'],
        ];

        /** @var array<string, array{0: string, 1: string}> $datasetsWithAtImportRule */
        $datasetsWithAtImportRule = DataProviders::cross(
            $equivalentCssWithAtImportRule,
            $equivalentCssWithAtImportRule
        );

        return $datasetsWithAtMediaRuleSelectorListAndPropertyDeclaration + $datasetsWithAtImportRule;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function provideEquivalentCssInStyleTags(): array
    {
        $datasetsWithoutStyleTags = $this->provideEquivalentCss();

        $datasetsWithRenamedKeys = static::arrayMapKeys(
            static function (string $description): string {
                return $description . ' in <style> tag';
            },
            $datasetsWithoutStyleTags
        );

        $datasets = \array_map(
            /**
             * @param array{0: string, 1: string} $dataset
             *
             * @return array{0: string, 1: string}
             */
            static function (array $dataset): array {
                return \array_map(
                    static function (string $css): string {
                        return '<style>' . $css . '</style>';
                    },
                    $dataset
                );
            },
            $datasetsWithRenamedKeys
        );

        return $datasets;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function provideCssNeedleFoundInLargerHaystack()
    {
        return [
            'needle at start of haystack' => ['p { color: green; }', 'p { color: green; } a { color: blue; }'],
            'needle at end of haystack' => ['p { color: green; }', 'body { font-size: 16px; } p { color: green; }'],
            'needle in middle of haystack' => [
                'p { color: green; }',
                'body { font-size: 16px; } p { color: green; } a { color: blue; }',
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function provideCssNeedleNotFoundInHaystack(): array
    {
        return [
            'CSS part with "{" not in CSS' => ['p {', 'body { color: green; }'],
            'CSS part with "}" not in CSS' => ['color: red; }', 'body { color: green; }'],
            'CSS part with "," not in CSS' => ['html, body', 'body { color: green; }'],
            'missing `;` after declaration where not optional' => [
                'body { color: green; font-size: 15px; }',
                "body { color: green\nfont-size: 15px; }",
            ],
            'extra `;` after declaration' => ['body { color: green; }', 'body { color: green;; }'],
            'spurious `;` after rule in at-rule' => [
                '@media print { body { color: green; } }',
                '@media print { body { color: green; }; }',
            ],
            'invalid space after `:` for pseudo-class' => [
                'p:first-child { color: green; }',
                'p: first-child { color: green; }',
            ],
            'pseudo-class without descendant combinator does not match with' => [
                'p:first-child { color: green; }',
                'p :first-child { color: green; }',
            ],
            'pseudo-class with descendant combinator does not match without' => [
                'p :first-child { color: green; }',
                'p:first-child { color: green; }',
            ],
            'missing required whitespace after at-rule identifier' => ['@media screen', '@mediascreen'],
            'missing required whitespace in calc before addition operator' => [
                'width: calc(1px + 50%);',
                'width: calc(1px+ 50%);',
            ],
            'missing required whitespace in calc before subtraction operator' => [
                'width: calc(50% - 1px);',
                'width: calc(50%- 1px);',
            ],
            'missing required whitespace in calc after addition operator' => [
                'width: calc(1px + 50%);',
                'width: calc(1px +50%);',
            ],
            '`@import` rule does not match with leading space inside quotes' => [
                '@import foo.css;',
                '@import " foo.css";',
            ],
            '`@import` rule does not match with leading line feed inside quotes' => [
                '@import foo.css;',
                "@import '\nfoo.css';",
            ],
            '`@import` rule does not match with leading Windows line ending inside quotes' => [
                '@import foo.css;',
                "@import '\r\nfoo.css';",
            ],
            '`@import` rule does not match with leading TAB inside quotes' => [
                '@import foo.css;',
                "@import '\tfoo.css';",
            ],
            '`@import` rule does not match with trailing space inside quotes' => [
                '@import foo.css;',
                '@import "foo.css ";',
            ],
            '`@import` rule does not match with leading space inside quotes with `url` function' => [
                '@import foo.css;',
                '@import url(" foo.css");',
            ],
            '`@import` rule does not match with trailing space inside quotes with `url` function' => [
                '@import foo.css;',
                '@import url("foo.css ");',
            ],
            '`@import` rule does not match with parameter in backticks' => ['@import foo.css;', '@import `foo.css`;'],
            '`@import` rule does not match with parameter in brackets' => ['@import foo.css;', '@import (foo.css);'],
            '`@import` rule does not match with misspelt `url` function' => [
                '@import foo.css;',
                '@import uri(foo.css);',
            ],
            '`@import` rule with media query does not match if media query is part of URL in quotes' => [
                '@import foo.css screen;',
                '@import "foo.css screen";',
            ],
            'more CSS than haystack' => ['p { color: green; } h1 { color: red; }', 'p { color: green; }'],
        ];
    }

    /**
     * @template T
     *
     * @param callable(string):string $callback
     * @param array<string, T> $array
     *
     * @return array<string, T>
     *
     * @throws \RuntimeException
     */
    private static function arrayMapKeys(callable $callback, array $array): array
    {
        $result = \array_combine(\array_map($callback, \array_keys($array)), \array_values($array));

        if ($result === false) {
            throw new \RuntimeException('`array_keys` and `array_values` did not give equal-length arrays', 1619201107);
        }

        return $result;
    }
}
