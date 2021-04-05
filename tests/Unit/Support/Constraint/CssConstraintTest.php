<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Constraint;

use Pelago\Emogrifier\Tests\Unit\Support\Constraint\Fixtures\TestingCssConstraint;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @covers \Pelago\Emogrifier\Tests\Support\Constraint\CssConstraint
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 */
final class CssConstraintTest extends TestCase
{
    /**
     * @test
     */
    public function getCssNeedleRegularExpressionPatternEscapesAllSpecialCharacters(): void
    {
        $needle = '.\\+*?[^]$(){}=!<>|:-/';

        $result = TestingCssConstraint::getCssNeedleRegularExpressionPatternForTesting($needle);

        $resultWithOtherMatchersRemoved = \str_replace(['(?:\\s*+;)?+', '\\s*+'], '', $result);

        self::assertSame(
            '/' . \preg_quote($needle, '/') . '/',
            $resultWithOtherMatchersRemoved
        );
    }

    /**
     * @test
     */
    public function getCssNeedleRegularExpressionPatternNotEscapesNonSpecialCharacters(): void
    {
        $needle = \implode('', \array_merge(\range('a', 'z'), \range('A', 'Z'), \range('0 ', '9 ')))
            . '`¬"£%&_;\'@~,';

        $result = TestingCssConstraint::getCssNeedleRegularExpressionPatternForTesting($needle);

        $resultWithWhitespaceMatchersRemoved = \str_replace('\\s*+', '', $result);

        self::assertSame(
            '/' . $needle . '/',
            $resultWithWhitespaceMatchersRemoved
        );
    }

    /**
     * @return string[][]
     */
    public function contentWithOptionalWhitespaceDataProvider(): array
    {
        return [
            '"{" alone' => ['{', ''],
            '"}" alone' => ['}', ''],
            '";" alone' => [';', ''],
            '"," alone' => [',', ''],
            '":" alone' => [':', ''],
            '"{" with non-special character' => ['{', 'a'],
            '"{" with two non-special characters' => ['{', 'a0'],
            '"{" with special character' => ['{', '.'],
            '"{" with two special characters' => ['{', '.+'],
            '"{" with special character and non-special character' => ['{', '.a'],
        ];
    }

    /**
     * @test
     *
     * @param string $contentToInsertAround
     * @param string $otherContent
     *
     * @dataProvider contentWithOptionalWhitespaceDataProvider
     */
    public function getCssNeedleRegularExpressionPatternInsertsOptionalWhitespace(
        string $contentToInsertAround,
        string $otherContent
    ): void {
        $result = TestingCssConstraint::getCssNeedleRegularExpressionPatternForTesting(
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
    public function getCssNeedleRegularExpressionPatternReplacesWhitespaceAtStartWithOptionalWhitespace(): void
    {
        $result = TestingCssConstraint::getCssNeedleRegularExpressionPatternForTesting(' a');

        self::assertSame('/\\s*+a/', $result);
    }

    /**
     * @return string[][]
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
     * @param string $needle
     *
     * @dataProvider styleTagDataProvider
     */
    public function getCssNeedleRegularExpressionPatternInsertsOptionalWhitespaceAfterStyleTag(string $needle): void
    {
        $result = TestingCssConstraint::getCssNeedleRegularExpressionPatternForTesting($needle);

        self::assertSame('/\\<style\\>\\s*+a/', $result);
    }

    /**
     * @return array<string, array<string>>
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
     * @param string $needle
     *
     * @dataProvider provideWhitespaceBetweenWords
     */
    public function getCssNeedleRegularExpressionPatternReplacesWhitespaceWithVariableWhitespace(string $needle): void
    {
        $result = TestingCssConstraint::getCssNeedleRegularExpressionPatternForTesting($needle);

        self::assertStringContainsString('a\\s++b', $result);
    }
}
