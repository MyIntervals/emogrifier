<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Constraint;

use Pelago\Emogrifier\Tests\Unit\Support\Constraint\Fixtures\TestingCssConstraint;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Tests\Support\Constraint\CssConstraint
 */
final class CssConstraintTest extends TestCase
{
    /**
     * @test
     */
    public function getCssRegularExpressionMatcherEscapesAllSpecialCharacters(): void
    {
        $css = '.\\+*?[^]$(){}=!<>|:-/';

        $result = TestingCssConstraint::getCssRegularExpressionMatcherForTesting($css);

        $resultWithOtherMatchersRemoved = \str_replace(['(?:\\s*+;)?+', '\\s*+'], '', $result);

        self::assertSame(
            \preg_quote($css, '/'),
            $resultWithOtherMatchersRemoved
        );
    }

    /**
     * @test
     */
    public function getCssRegularExpressionMatcherNotEscapesNonSpecialCharacters(): void
    {
        $css = \implode('', \array_merge(\range('a', 'z'), \range('A', 'Z'), \range('0 ', '9 '))) . '`¬"£%&_;\'@~,';

        $result = TestingCssConstraint::getCssRegularExpressionMatcherForTesting($css);

        $resultWithWhitespaceMatchersRemoved = \str_replace('\\s*+', '', $result);

        self::assertSame($css, $resultWithWhitespaceMatchersRemoved);
    }

    /**
     * @return array<non-empty-string, array{0: non-empty-string, 1: string}>
     */
    public function contentWithOptionalWhitespaceDataProvider(): array
    {
        return [
            '"{" alone' => ['{', ''],
            '"}" alone' => ['}', ''],
            '";" alone' => [';', ''],
            '"," alone' => [',', ''],
            '":" alone' => [':', ''],
            '">" alone' => ['>', ''],
            '"+" alone' => ['+', ''],
            '"~" alone' => ['~', ''],
            '"{" with non-special character' => ['{', 'a'],
            '"{" with two non-special characters' => ['{', 'a0'],
            '"{" with special character' => ['{', '.'],
            '"{" with two special characters' => ['{', '.*'],
            '"{" with special character and non-special character' => ['{', '.a'],
        ];
    }

    /**
     * @test
     *
     * @param non-empty-string $contentToInsertAround
     *
     * @dataProvider contentWithOptionalWhitespaceDataProvider
     */
    public function getCssRegularExpressionMatcherInsertsOptionalWhitespace(
        string $contentToInsertAround,
        string $otherContent
    ): void {
        $result = TestingCssConstraint::getCssRegularExpressionMatcherForTesting(
            $otherContent . $contentToInsertAround . $otherContent
        );

        $quotedOtherContent = \preg_quote($otherContent, '/');
        $expectedResult = $quotedOtherContent . '\\s*+' . \preg_quote($contentToInsertAround, '/') . '\\s*+'
            . $quotedOtherContent;

        self::assertStringContainsString($expectedResult, $result);
    }

    /**
     * @test
     */
    public function getCssRegularExpressionMatcherReplacesWhitespaceAtStartWithOptionalWhitespace(): void
    {
        $result = TestingCssConstraint::getCssRegularExpressionMatcherForTesting(' a');

        self::assertSame('\\s*+a', $result);
    }

    /**
     * @return array<non-empty-string, array{0: non-empty-string}>
     */
    public function styleTagDataProvider(): array
    {
        return [
            'without space after' => ['<style>a'],
            'one space after' => ['<style> a'],
            'two spaces after' => ['<style>  a'],
            'linefeed after' => ["<style>\na"],
            'Windows line ending after' => ["<style>\r\na"],
        ];
    }

    /**
     * @test
     *
     * @param non-empty-string $css
     *
     * @dataProvider styleTagDataProvider
     */
    public function getCssRegularExpressionMatcherInsertsOptionalWhitespaceAfterStyleTag(string $css): void
    {
        $result = TestingCssConstraint::getCssRegularExpressionMatcherForTesting($css);

        self::assertStringEndsWith('>\\s*+a', $result);
    }

    /**
     * @return array<non-empty-string, array{0: non-empty-string}>
     */
    public function provideWhitespaceBetweenWords(): array
    {
        return [
            'one space' => ['a b'],
            'two spaces' => ['a  b'],
            'linefeed' => ["a\nb"],
            'Windows line ending' => ["a\r\nb"],
            'tab' => ["a\tb"],
        ];
    }

    /**
     * @test
     *
     * @param non-empty-string $css
     *
     * @dataProvider provideWhitespaceBetweenWords
     */
    public function getCssRegularExpressionMatcherReplacesWhitespaceWithVariableWhitespace(string $css): void
    {
        $result = TestingCssConstraint::getCssRegularExpressionMatcherForTesting($css);

        self::assertSame('a\\s++b', $result);
    }
}
