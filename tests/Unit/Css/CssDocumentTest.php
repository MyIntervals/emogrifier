<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Css;

use Pelago\Emogrifier\Css\CssDocument;
use Pelago\Emogrifier\Tests\Support\Traits\AssertCss;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Css\CssDocument
 */
final class CssDocumentTest extends TestCase
{
    use AssertCss;

    /**
     * @var string
     */
    private const VALID_AT_FONT_FACE_RULE = '@font-face {' . "\n"
        . '  font-family: "Foo Sans";' . "\n"
        . '  src: url("/foo-sans.woff2") format("woff2");' . "\n}";

    /**
     * @test
     *
     * @param string $selector
     *
     * @dataProvider provideSelector
     * @dataProvider provideSelectorWithVariedWhitespace
     */
    public function parsesSelector(string $selector): void
    {
        $css = $selector . '{ color: green; }';
        $subject = new CssDocument($css);

        $result = $subject->getStyleRulesData([]);

        self::assertCount(1, $result);
        self::assertEquivalentCss($selector, $result[0]->getSelectors()[0]);
    }

    /**
     * @test
     */
    public function canParsesMultipleSelectors(): void
    {
        $css = 'h1, h2 { color: green; }';
        $subject = new CssDocument($css);

        $result = $subject->getStyleRulesData([]);

        self::assertCount(1, $result);
        self::assertSame(['h1', 'h2'], $result[0]->getSelectors());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideSelector(): array
    {
        return [
            'universal' => ['*'],
            'type' => ['p'],
            'class' => ['.classy'],
            'ID' => ['#toc'],
            'attribute (presence)' => ['[title]'],
            'attribute (value match)' => ['[href=https://example.org]'],
            'attribute (word match)' => ['[class~=logo]'],
            'attribute (hyphenated prefix match)' => ['[lang|=de]'],
            'attribute (prefix match)' => ['[href^=#]'],
            'attribute (suffix match)' => ['[href$=.org]'],
            'attribute (substring match)' => ['[href*=example]'],
            'attribute (match with single quotes)' => ['[href=\'https://example.org\']'],
            'attribute (match with double quotes)' => ['[href="https://example.org"]'],
            'attribute (case insensitive match)' => ['[href*=example i]'],
            'pseudo class' => [':hover'],
            'pseudo element' => ['::after'],
            'vendor pseudo class' => [':-webkit-autofill'],
            'vendor pseudo element' => ['::-webkit-progress-bar'],
            'combined' => ['p.classy'],
            'descendant' => ['p .classy'],
            'child' => ['p > .classy'],
            'general sibling' => ['h1 ~ p'],
            'adjacent sibling' => ['h1 + p'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideSelectorWithVariedWhitespace(): array
    {
        return [
            'with space after' => ['p '],
            'with line feed after' => ["p\n"],
            'with Windows line ending after' => ["p\r\n"],
            'with TAB after' => ["p\t"],
            'minified' => ['p>.classy'],
            'with line feeds within' => ["p\n>\n.classy"],
        ];
    }

    /**
     * @test
     *
     * @param string $declarations
     *
     * @dataProvider provideDeclarations
     * @dataProvider provideDeclarationsWithVariedWhitespace
     */
    public function parsesDeclarations(string $declarations): void
    {
        $css = 'p {' . $declarations . '}';
        $subject = new CssDocument($css);

        $result = $subject->getStyleRulesData([]);

        self::assertCount(1, $result);
        self::assertEquivalentCss($declarations, $result[0]->getDeclarationsBlock());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideDeclarations(): array
    {
        return [
            'colour (named)' => ['color: green;'],
            'colour (RGB hex)' => ['color: #0F0;'],
            'colour (RGBA)' => ['color: rgba(51, 170, 51, .1);'],
            'length (px)' => ['margin-top: 10px;'],
            'length (%)' => ['margin-right: 10%;'],
            'shorthand' => ['margin: 0 auto;'],
            'shorthand with mixed types' => ['background: no-repeat center/80% url("../img/image.png");'],
            'calc' => ['margin-right: calc(10% + 2px);'],
            'vendor property' => ['-webkit-appearance: button;'],
            'vendor value' => [
                'background-image: '
                . '-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #83bc38), color-stop(1, #57873b));',
            ],
            'multiple' => ['color: green; text-decoration: underline;'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideDeclarationsWithVariedWhitespace(): array
    {
        return [
            'minified' => ['color:green'],
            'with space before' => [' color: green;'],
            'with line feed before' => ["\ncolor: green;"],
            'with Windows line ending before' => ["\r\ncolor: green;"],
            'with TAB before' => ["\tcolor: green;"],
            'with line feed within' => ["color:\ngreen;"],
            'with line feed after' => ["color: green;\n"],
            'multiple (minified)' => ['color:green;text-decoration:underline'],
            'multiple (separated by line feed)' => ["color: green;\ntext-decoration: underline;"],
            'multiple (separated by Windows line ending)' => ["color: green;\r\ntext-decoration: underline;"],
            'multiple (separated by TAB)' => ["color: green;\ttext-decoration: underline;"],
        ];
    }

    /**
     * @test
     *
     * @param string $mediaQuery
     *
     * @dataProvider provideMediaQuery
     */
    public function parsesAtMediaRule(string $mediaQuery): void
    {
        $atMediaAndQuery = '@media ' . $mediaQuery;
        $css = $atMediaAndQuery . ' { p { color: green; } }';
        $subject = new CssDocument($css);

        $result = $subject->getStyleRulesData(['screen']);

        self::assertCount(1, $result);
        self::assertSameTrimmed($atMediaAndQuery, $result[0]->getMediaQuery());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideMediaQuery(): array
    {
        return [
            'type' => ['screen'],
            'type with `only`' => ['only screen'],
            'plain value' => ['(max-width: 480px)'],
            // broken: 'plain value with `not`' => ['not (max-width: 480px)'],
            'plain values with `and`' => ['(min-width: 320px) and (max-width: 480px)'],
            'plain values with `or`' => ['(max-width: 320px) or (min-width: 480px)'],
            'type and plain value' => ['screen and (max-width: 480px)'],
            'range (singly bounded)' => ['(height > 600px)'],
            'range (fully bounded)' => ['(400px <= width <= 700px)'],
        ];
    }

    /**
     * @test
     *
     * @param string $whitespaceAfterAtMedia
     * @param string $optionalWhitespaceWithinRule
     *
     * @dataProvider provideVariedWhitespaceForAtMediaRule
     */
    public function parsesAtMediaRuleWithVariedWhitespace(
        string $whitespaceAfterAtMedia,
        string $optionalWhitespaceWithinRule
    ): void {
        $atMediaAndQuery = '@media' . $whitespaceAfterAtMedia . 'screen';
        $css = $atMediaAndQuery . $optionalWhitespaceWithinRule
            . '{' . $optionalWhitespaceWithinRule . 'p { color: green; }' . $optionalWhitespaceWithinRule . '}';
        $subject = new CssDocument($css);

        $result = $subject->getStyleRulesData(['screen']);

        self::assertCount(1, $result);
        self::assertEquivalentCss($atMediaAndQuery, $result[0]->getMediaQuery());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideVariedWhitespaceForAtMediaRule(): array
    {
        return [
            // Space before media query is already covered by `parsesAtMediaRule`, as are spaces within rule.
            'line feed before media query' => ["\n", ''],
            'Windows line ending before media query' => ["\r\n", ''],
            'TAB before media query' => ["\t", ''],
            'line feeds within rule' => [' ', "\n"],
            'Windows line endings within rule' => [' ', "\r\n"],
            'TABs within rule' => [' ', "\t"],
        ];
    }

    /**
     * @test
     *
     * @param string $mediaQuery
     *
     * @dataProvider provideMediaQueryWithTvType
     * @dataProvider provideMediaQueryWithTvTypeAndVariedWhitespace
     */
    public function discardsMediaRuleWithTypeNotInAllowlist(string $mediaQuery): void
    {
        $subject = new CssDocument('@media ' . $mediaQuery . ' { p { color: red; } }');

        $result = $subject->getStyleRulesData(['screen']);

        self::assertCount(0, $result);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideMediaQueryWithTvType(): array
    {
        return [
            'type alone' => ['tv'],
            'type with `only`' => ['only tv'],
            'type and plain value' => ['tv and (max-width: 480px)'],
            'type and plain value with `not`' => ['tv and not (max-width: 480px)'],
            'type and plain values with `and`' => ['tv and (min-width: 320px) and (max-width: 480px)'],
            'type and range (singly bounded)' => ['tv and (height > 600px)'],
            'type and range (fully bounded)' => ['tv and (400px <= width <= 700px)'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideMediaQueryWithTvTypeAndVariedWhitespace(): array
    {
        return [
            'with line feed before' => ["\ntv"],
            'with Windows line ending before' => ["\r\ntv"],
            'with TAB before' => ["\ttv"],
        ];
    }

    /**
     * @test
     *
     * @param string $cssBetween
     *
     * @dataProvider provideCssWithoutStyleRules
     */
    public function parsesMultipleStyleRulesWithOtherCssBetween(string $cssBetween): void
    {
        $subject = new CssDocument('p { color: green; }' . $cssBetween . '@media screen { h1 { color: green; } }');

        $result = $subject->getStyleRulesData(['screen']);

        // The content of the parsed rules is covered by other tests.  Here just check the number of parsed rules.
        self::assertCount(2, $result);
    }

    /**
     * @test
     *
     * @param string $cssBefore
     *
     * @dataProvider provideCssWithoutStyleRules
     * @dataProvider provideCssThatMustPrecedeStyleRules
     */
    public function parsesMultipleStyleRulesWithOtherCssBefore(string $cssBefore): void
    {
        $subject = new CssDocument(
            $cssBefore . 'p { color: green; } @media screen { h1 { color: green; } }'
        );

        $result = $subject->getStyleRulesData(['screen']);

        // The content of the parsed rules is covered by other tests.  Here just check the number of parsed rules.
        self::assertCount(2, $result);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideCssWithoutStyleRules(): array
    {
        return [
            'nothing' => [''],
            'space' => [' '],
            'line feed' => ["\n"],
            'Windows line ending' => ["\r\n"],
            'TAB' => ["\r\n"],
            'non-conditional at-rule (valid `@font-face`)' => [self::VALID_AT_FONT_FACE_RULE],
            'comment' => ['/* Test */'],
            'commented-out style rule' => ['/* p { color: red; } */'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideCssThatMustPrecedeStyleRules(): array
    {
        return [
            '`@charset` rule' => ['@charset "UTF-8";'],
            '`@import` rule' => ['@import "foo.css";'],
        ];
    }

    /**
     * @test
     *
     * @param string $atRuleCss
     * @param string $cssBefore
     *
     * @dataProvider provideValidNonConditionalAtRule
     */
    public function rendersValidNonConditionalAtRule(string $atRuleCss, string $cssBefore = ''): void
    {
        $subject = new CssDocument($cssBefore . $atRuleCss);

        $result = $subject->renderNonConditionalAtRules();

        self::assertContainsCss($atRuleCss, $result);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideValidNonConditionalAtRule(): array
    {
        return [
            '`@import`' => ['@import "foo.css";'],
            '`@font-face`' => [self::VALID_AT_FONT_FACE_RULE],
            '`@import` after `@charset`' => ['@import "foo.css";', '@charset "UTF-8";'],
            '`@import` after invalid `@charset` (uppercase identifier)' => ['@import "foo.css";', '@CHARSET "UTF-8";'],
            '`@import` after invalid `@charset` (extra space)' => ['@import "foo.css";', '@charset  "UTF-8";'],
            // broken: `@import` after invalid `@charset` (unquoted value)
            '`@import` after `@import`' => ['@import "foo.css";', '@import "bar.css";'],
            '`@import` after space' => ['@import "foo.css";', ' '],
            '`@import` after line feed' => ['@import "foo.css";', "\n"],
            '`@import` after Windows line ending' => ['@import "foo.css";', "\r\n"],
            '`@import` after TAB' => ['@import "foo.css";', "\t"],
            '`@import` after comment' => ['@import "foo.css";', '/* Test */'],
            '`@import` after commented-out `@font-face` rule' => [
                '@import "foo.css";',
                '/* ' . self::VALID_AT_FONT_FACE_RULE . ' */',
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $cssBetween
     *
     * @dataProvider provideCssWithoutNonConditionalAtRules
     */
    public function rendersMultipleNonConditionalAtRules(string $cssBetween): void
    {
        $subject = new CssDocument('@import "foo.css";' . $cssBetween . self::VALID_AT_FONT_FACE_RULE);

        $result = $subject->renderNonConditionalAtRules();

        // The content of the rendered rules is covered by other tests.  Here just check the number of rendered rules.
        self::assertSame(2, \substr_count($result, '@'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideCssWithoutNonConditionalAtRules(): array
    {
        return [
            'nothing' => [''],
            'space' => [' '],
            'line feed' => ["\n"],
            'Windows line ending' => ["\r\n"],
            'TAB' => ["\r\n"],
            'style rule' => ['p { color: red; }'],
            '`@media` rule' => ['@media screen { p { color: red; } }'],
            'comment' => ['/* Test */'],
            'commented-out `@font-face` rule' => ['/* ' . self::VALID_AT_FONT_FACE_RULE . ' */'],
        ];
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider provideValidAtCharsetRules
     * @dataProvider provideInvalidAtCharsetRules
     */
    public function discardsValidOrInvalidAtCharsetRule(string $css): void
    {
        $subject = new CssDocument($css);

        $result = $subject->renderNonConditionalAtRules();

        self::assertSame('', $result);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function provideValidAtCharsetRules(): array
    {
        return [
            'UTF-8' => ['@charset "UTF-8";'],
            'iso-8859-15' => ['@charset "iso-8859-15";'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function provideInvalidAtCharsetRules(): array
    {
        return [
            'with uppercase identifier' => ['@CHARSET "UTF-8";'],
            'with extra space' => ['@charset  "UTF-8";'],
            'with unquoted value' => ['@charset UTF-8;'],
        ];
    }

    /**
     * @test
     *
     * @param string $atRuleCss
     * @param string $cssBefore
     *
     * @dataProvider provideInvalidNonConditionalAtRule
     */
    public function notRendersInvalidNonConditionalAtRule(string $atRuleCss, string $cssBefore = ''): void
    {
        $subject = new CssDocument($cssBefore . $atRuleCss);

        $result = $subject->renderNonConditionalAtRules();

        \preg_match('/@[\\w\\-]++/', $atRuleCss, $atAndRuleNameMatches);
        $atAndRuleName = $atAndRuleNameMatches[0];
        self::assertStringNotContainsString($atAndRuleName, $result);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideInvalidNonConditionalAtRule(): array
    {
        return [
            '`@font-face` without `font-family`' => ['
                @font-face {
                  src: url("/foo-sans.woff2") format("woff2");
                }
            '],
            '`@font-face` without `src`' => ['
                @font-face {
                  font-family: "Foo Sans";
                }
            '],
            '`@charset` after style rule' => ['@charset "UTF-8";', 'p { color: red; }'],
            '`@charset` after `@import` rule' => ['@charset "UTF-8";', '@import "foo.css";'],
            '`@import` after style rule' => ['@import "foo.css";', 'p { color: red; }'],
            '`@import` after `@font-face` rule' => ['@import "foo.css";', self::VALID_AT_FONT_FACE_RULE],
        ];
    }

    /**
     * @test
     */
    public function notRendersAtMediaRuleInNonConditionalAtRules(): void
    {
        $subject = new CssDocument('@media screen { p { color: red; } }');

        $result = $subject->renderNonConditionalAtRules();

        self::assertSame('', $result);
    }

    /**
     * Asserts that two strings are the same after `trim`ming both of them.
     *
     * @param string $expected
     * @param string $actual
     */
    private static function assertSameTrimmed(string $expected, string $actual): void
    {
        self::assertSame(\trim($expected), \trim($actual));
    }
}
