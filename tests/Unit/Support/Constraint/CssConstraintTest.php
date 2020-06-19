<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Constraint;

use Pelago\Emogrifier\Tests\Support\Constraint\CssConstraint;
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
    use CssConstraint;

    /**
     * @test
     */
    public function getCssNeedleRegExpEscapesAllSpecialCharacters(): void
    {
        $needle = '.\\+*?[^]$(){}=!<>|:-/';

        $result = self::getCssNeedleRegExp($needle);

        $resultWithWhitespaceMatchersRemoved = \str_replace('\\s*+', '', $result);

        self::assertSame(
            '/' . \preg_quote($needle, '/') . '/',
            $resultWithWhitespaceMatchersRemoved
        );
    }

    /**
     * @test
     */
    public function getCssNeedleRegExpNotEscapesNonSpecialCharacters(): void
    {
        $needle = \implode('', \array_merge(\range('a', 'z'), \range('A', 'Z'), \range('0 ', '9 ')))
            . "\r\n\t `¬\"£%&_;'@~,";

        $result = self::getCssNeedleRegExp($needle);

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
            '"," alone' => [',', ''],
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
    public function getCssNeedleRegExpInsertsOptionalWhitespace(
        string $contentToInsertAround,
        string $otherContent
    ): void {
        $result = self::getCssNeedleRegExp($otherContent . $contentToInsertAround . $otherContent);

        $quotedOtherContent = \preg_quote($otherContent, '/');
        $expectedResult = '/' . $quotedOtherContent . '\\s*+' . \preg_quote($contentToInsertAround, '/') . '\\s*+'
            . $quotedOtherContent . '/';

        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function getCssNeedleRegExpReplacesWhitespaceAtStartWithOptionalWhitespace(): void
    {
        $result = self::getCssNeedleRegExp(' a');

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
    public function getCssNeedleRegExpInsertsOptionalWhitespaceAfterStyleTag(string $needle): void
    {
        $result = self::getCssNeedleRegExp($needle);

        self::assertSame('/\\<style\\>\\s*+a/', $result);
    }
}
