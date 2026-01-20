<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\HtmlProcessor;

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\CssVariableEvaluator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\HtmlProcessor\CssVariableEvaluator
 */
final class CssVariableEvaluatorTest extends TestCase
{
    private const COMMON_TEST_HTML = '
        <html>
            <head>
                <title>CssVariableEvaluator Test</title>
            </head>
            <body>
                <h1>CssVariableEvaluator Test</h1>
                <p>
                    This tests the <code>CssVariableEvaluator</code> class.
                </p>
            </body>
        </html>
    ';

    /**
     * @test
     */
    public function evaluateVariablesProvidesFluentInterface(): void
    {
        $subject = CssVariableEvaluator::fromHtml('<html></html>');

        $result = $subject->evaluateVariables();

        self::assertSame($subject, $result);
    }

    /**
     * Invokes `CssInliner` to provide a test subject wrapping a `DOMDocument` in which `$css` has been inlined into
     * `$html`.
     *
     * @param non-empty-string $html
     */
    private function buildSubjectWithCssInlined(string $html, string $css): CssVariableEvaluator
    {
        $cssInliner = CssInliner::fromHtml($html);
        $cssInliner->inlineCss($css);

        return CssVariableEvaluator::fromDomDocument($cssInliner->getDomDocument());
    }

    /**
     * @return array<non-empty-string, array{css: non-empty-string, expect: non-empty-string}>
     */
    public function provideCssUsingVariablesAndExpectedHtmlFragmentAfterInliningAndEvaluation(): array
    {
        return [
            'undefined variable' => [
                'css' => '
                    p {
                        color: var(--text-color);
                    }
                ',
                'expect' => '<p style="color: var(--text-color);">',
            ],
            'variable defined in root, used in descendant' => [
                'css' => '
                    :root {
                        --text-color: green;
                    }
                    p {
                        color: var(--text-color);
                    }
                ',
                'expect' => '<p style="color: green;">',
            ],
            'variable defined in parent, used in child' => [
                'css' => '
                    p {
                        --text-color: green;
                    }
                    code {
                        color: var(--text-color);
                    }
                ',
                'expect' => '<code style="color: green;">',
            ],
            'variable defined and used in same element' => [
                'css' => '
                    p {
                        --text-color: green;
                    }
                    p {
                        color: var(--text-color);
                    }
                ',
                // The variable definition is not removed, but its value should nonetheless be applied
                'expect' => '<p style="--text-color: green; color: green;">',
            ],
            'variable defined in parent and root' => [
                'css' => '
                    :root {
                        --text-color: red;
                    }
                    p {
                        --text-color: green;
                    }
                    code {
                        color: var(--text-color);
                    }
                ',
                'expect' => '<code style="color: green;">',
            ],
            'variable defined and used in same element, also defined in root' => [
                'css' => '
                    :root {
                        --text-color: red;
                    }
                    p {
                        --text-color: green;
                    }
                    p {
                        color: var(--text-color);
                    }
                ',
                // The variable definition is not removed, but its value should nonetheless be applied
                'expect' => '<p style="--text-color: green; color: green;">',
            ],
            'variable defined only for descendant' => [
                'css' => '
                    code {
                        --text-color: red;
                    }
                    p {
                        color: var(--text-color);
                    }
                ',
                'expect' => '<p style="color: var(--text-color);">',
            ],
            'variable defined for root and descendant' => [
                'css' => '
                    :root {
                        --text-color: green;
                    }
                    code {
                        --text-color: red;
                    }
                    p {
                        color: var(--text-color);
                    }
                ',
                'expect' => '<p style="color: green;">',
            ],
            'variable name with uppercase characters' => [
                'css' => '
                    :root {
                        --Text-Color: green;
                    }
                    p {
                        color: var(--Text-Color);
                    }
                ',
                'expect' => '<p style="color: green;">',
            ],
            'variable defined in parent and root but differently cased' => [
                'css' => '
                    :root {
                        --text-color: green;
                    }
                    p {
                        --Text-Color: red;
                    }
                    code {
                        color: var(--text-color);
                    }
                ',
                'expect' => '<code style="color: green;">',
            ],
            'with whitespace around `var` argument' => [
                'css' => '
                    :root {
                        --text-color: green;
                    }
                    p {
                        color: var(
                            --text-color
                        );
                    }
                ',
                'expect' => '<p style="color: green;">',
            ],
            'multiple variables used in property value' => [
                'css' => '
                    :root {
                        --scale: 1.2;
                        --size: 0.8rem;
                    }
                    p {
                        font-size: calc(var(--size) * var(--scale));
                    }
                ',
                // Processing through `CssInliner` results in `0.8rem` being optimized to `.8rem`.
                'expect' => '<p style="font-size: calc(.8rem * 1.2);"',
            ],
            'fallback value provided for undefined variable' => [
                'css' => '
                    p {
                        color: var(--text-color, green);
                    }
                ',
                'expect' => '<p style="color: green;">',
            ],
            'fallback value provided for defined variable' => [
                'css' => '
                    :root {
                        --text-color: green;
                    }
                    p {
                        color: var(--text-color, red);
                    }
                ',
                'expect' => '<p style="color: green;">',
            ],
            'fallback value refererencing another variable which is defined' => [
                'css' => '
                    :root {
                        --default-text-color: green;
                    }
                    p {
                        color: var(--text-color, var(--default-text-color));
                    }
                ',
                'expect' => '<p style="color: green;">',
            ],
            'fallback value refererencing another variable which is also not defined' => [
                'css' => '
                    p {
                        color: var(--text-color, var(--default-text-color));
                    }
                ',
                // The expected behaviour here is somewhat ambiguous.
                'expect' => '<p style="color: var(--default-text-color);">',
            ],
            'nested fallback value' => [
                'css' => '
                    p {
                        color: var(--text-color, var(--default-text-color, green));
                    }
                ',
                'expect' => '<p style="color: green;">',
            ],
            'fallback value with parentheses' => [
                'css' => '
                    body {
                        width: var(--page-width, calc(100vw - 20px));
                    }
                ',
                'expect' => '<body style="width: calc(100vw - 20px);">',
            ],
            'fallback value with nested parentheses' => [
                'css' => '
                    body {
                        width: var(--page-width, calc(100vw - 2 * calc(1rem + 10px)));
                    }
                ',
                'expect' => '<body style="width: calc(100vw - 2 * calc(1rem + 10px));">',
            ],
            // Processing through `CssInliner` may result in the quotes being changed.
            // A direct test with HTML is also performed for the following four scenarios.
            'fallback value with single-quoted string containing opening parenthesis' => [
                'css' => '
                    h1 {
                        content: var(--main-heading, \'Missing heading :(\');
                    }
                ',
                'expect' => '<h1 style=\'content: "Missing heading :(";\'>',
            ],
            'fallback value with single-quoted string containing closing parenthesis' => [
                'css' => '
                    h1 {
                        content: var(--main-heading, \'Missing heading ):\');
                    }
                ',
                'expect' => '<h1 style=\'content: "Missing heading ):";\'>',
            ],
            'fallback value with double-quoted string containing opening parenthesis' => [
                'css' => '
                    h1 {
                        content: var(--main-heading, "Missing heading :(");
                    }
                ',
                'expect' => '<h1 style=\'content: "Missing heading :(";\'>',
            ],
            'fallback value with double-quoted string containing closing parenthesis' => [
                'css' => '
                    h1 {
                        content: var(--main-heading, "Missing heading ):");
                    }
                ',
                'expect' => '<h1 style=\'content: "Missing heading ):";\'>',
            ],
        ];
    }

    /**
     * @test
     *
     * This test simplifies the provision of test data by using an initial `CssInliner` step on some standard HTML,
     * so that CSS can be provided as the test data rather than direct HTML.
     *
     * @param non-empty-string $css
     * @param non-empty-string $expectedHtmlFragment
     *
     * @dataProvider provideCssUsingVariablesAndExpectedHtmlFragmentAfterInliningAndEvaluation
     */
    public function replacesReferencedVariableIfDefinedOrNotOtherwise(string $css, string $expectedHtmlFragment): void
    {
        $subject = $this->buildSubjectWithCssInlined(self::COMMON_TEST_HTML, $css);

        $htmlResult = $subject->evaluateVariables()->render();

        self::assertStringContainsString($expectedHtmlFragment, $htmlResult);
    }

    /**
     * @return array<non-empty-string, array{html: non-empty-string, expect: non-empty-string}>
     */
    public function provideHtmlUsingVariablesAndExpectedHtmlFragmentAfterInliningAndEvaluation(): array
    {
        return [
            // The `CssInliner` step in the test with CSS data might strip whitespace.
            'with whitespace around `var` argument' => [
                'html' => '
                    <html style="--text-color: green;">
                        <p
                            style="
                                color: var(
                                    --text-color
                                );
                            "
                        >
                        </p>
                    </html>
                ',
                'expect' => '<p style="color: green;">',
            ],
            // The `CssInliner` step in the test with CSS data might change quoting style.
            'fallback value with single-quoted string containing opening parenthesis' => [
                'html' => '<h1 style="content: var(--main-heading, \'Missing heading :(\');"></h1>',
                'expect' => '<h1 style="content: \'Missing heading :(\';">',
            ],
            'fallback value with single-quoted string containing closing parenthesis' => [
                'html' => '<h1 style="content: var(--main-heading, \'Missing heading ):\');"></h1>',
                'expect' => '<h1 style="content: \'Missing heading ):\';">',
            ],
            'fallback value with double-quoted string containing opening parenthesis' => [
                'html' => '<h1 style="content: var(--main-heading, &quot;Missing heading :(&quot;);"></h1>',
                'expect' => '<h1 style=\'content: "Missing heading :(";\'>',
            ],
            'fallback value with double-quoted string containing closing parenthesis' => [
                'html' => '<h1 style="content: var(--main-heading, &quot;Missing heading ):&quot;);"></h1>',
                'expect' => '<h1 style=\'content: "Missing heading ):";\'>',
            ],
        ];
    }

    /**
     * @test
     *
     * The test method {@see replacesReferencedVariableIfDefinedOrNotOtherwise} may result in changes to the quoting
     * style of CSS property values (single vs double -quotes).
     * This method allows testing with HTML directly.
     *
     * @param non-empty-string $html
     * @param non-empty-string $expectedHtmlFragment
     *
     * @dataProvider provideHtmlUsingVariablesAndExpectedHtmlFragmentAfterInliningAndEvaluation
     */
    public function replacesReferencedVariableIfDefinedOrNotOtherwiseInHtml(
        string $html,
        string $expectedHtmlFragment
    ): void {
        $subject = CssVariableEvaluator::fromHtml($html);

        $htmlResult = $subject->evaluateVariables()->render();

        self::assertStringContainsString($expectedHtmlFragment, $htmlResult);
    }

    /**
     * @return array<string, array{0: positive-int}>
     */
    public function provideNestingLevel(): array
    {
        return [
            '12 deep' => [12],
            '257 deep' => [257],
            // Temporarily disabled pending fix for #1555
            //'513 deep' => [513],
            //'1300 deep' => [1300],
        ];
    }

    /**
     * @test
     *
     * @param positive-int $nestingLevel
     *
     * @dataProvider provideNestingLevel
     */
    public function supportsDeeplyNestedHtml(int $nestingLevel): void
    {
        $this->expectNotToPerformAssertions();

        $html = \str_repeat('<div>', $nestingLevel) . \str_repeat('</div>', $nestingLevel);
        $subject = CssVariableEvaluator::fromHtml($html);

        $subject->evaluateVariables();
    }
}
