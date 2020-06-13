<?php

declare(strict_types=1);

namespace Pelago\Tests\Unit\Support\Traits;

use Pelago\Emogrifier\Tests\Support\Traits\AssertCss;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @covers \Pelago\Emogrifier\Tests\Support\Traits\AssertCss
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 */
class AssertCssTest extends TestCase
{
    use AssertCss;

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

    /**
     * @return string[][]
     */
    public function needleFoundDataProvider(): array
    {
        $cssStrings = [
            'unminified CSS' => 'html, body { color: green; }',
            'minified CSS' => 'html,body{color: green;}',
            'CSS with extra spaces' => '  html  ,  body  {  color: green;  }',
            'CSS with linefeeds' => "\nhtml\n,\nbody\n{\ncolor: green;\n}",
            'CSS with Windows line endings' => "\r\nhtml\r\n,\r\nbody\r\n{\r\ncolor: green;\r\n}",
        ];

        $datasets = [];
        foreach ($cssStrings as $needleDescription => $needle) {
            foreach ($cssStrings as $haystackDescription => $haystack) {
                $description = $needleDescription . ' in ' . $haystackDescription;
                $datasets[$description] = [$needle, $haystack];
                $datasets[$description . ' in <style> tag'] = [
                    '<style>' . $needle . '</style>',
                    '<style>' . $haystack . '</style>',
                ];
            }
        }
        return $datasets;
    }

    /**
     * @return string[][]
     */
    public function needleNotFoundDataProvider(): array
    {
        return [
            'CSS part with "{" not in CSS' => ['p {', 'body { color: green; }'],
            'CSS part with "}" not in CSS' => ['color: red; }', 'body { color: green; }'],
            'CSS part with "," not in CSS' => ['html, body', 'body { color: green; }'],
        ];
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     */
    public function assertContainsCssPassesTestIfNeedleFound(string $needle, string $haystack): void
    {
        self::assertContainsCss($needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleNotFoundDataProvider
     */
    public function assertContainsCssFailsTestIfNeedleNotFound(string $needle, string $haystack): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCss($needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleNotFoundDataProvider
     */
    public function assertNotContainsCssPassesTestIfNeedleNotFound(string $needle, string $haystack): void
    {
        self::assertNotContainsCss($needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     */
    public function assertNotContainsCssFailsTestIfNeedleFound(string $needle, string $haystack): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertNotContainsCss($needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleNotFoundDataProvider
     */
    public function assertContainsCssCountPassesTestExpectingZeroIfNeedleNotFound(
        string $needle,
        string $haystack
    ): void {
        self::assertContainsCssCount(0, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     */
    public function assertContainsCssCountFailsTestExpectingZeroIfNeedleFound(string $needle, string $haystack): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount(0, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     */
    public function assertContainsCssCountPassesTestExpectingOneIfNeedleFound(string $needle, string $haystack): void
    {
        self::assertContainsCssCount(1, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleNotFoundDataProvider
     */
    public function assertContainsCssCountFailsTestExpectingOneIfNeedleNotFound(string $needle, string $haystack): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount(1, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     */
    public function assertContainsCssCountFailsTestExpectingOneIfNeedleFoundTwice(
        string $needle,
        string $haystack
    ): void {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount(1, $needle, $haystack . $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     */
    public function assertContainsCssCountPassesTestExpectingTwoIfNeedleFoundTwice(
        string $needle,
        string $haystack
    ): void {
        self::assertContainsCssCount(2, $needle, $haystack . $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleNotFoundDataProvider
     */
    public function assertContainsCssCountFailsTestExpectingTwoIfNeedleNotFound(string $needle, string $haystack): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount(2, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     */
    public function assertContainsCssCountFailsTestExpectingTwoIfNeedleFoundOnlyOnce(
        string $needle,
        string $haystack
    ): void {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount(2, $needle, $haystack);
    }
}
