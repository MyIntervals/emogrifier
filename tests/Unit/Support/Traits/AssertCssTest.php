<?php

namespace Pelago\Tests\Unit\Support\Traits;

use Pelago\Tests\Support\Traits\AssertCss;

/**
 * Test case.
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 */
class AssertCssTest extends \PHPUnit_Framework_TestCase
{
    use AssertCss;

    /**
     * @test
     */
    public function getCssNeedleRegExpEscapesAllSpecialCharacters()
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
    public function getCssNeedleRegExpNotEscapesNonSpecialCharacters()
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
    public function contentWithOptionalWhitespaceDataProvider()
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
    public function getCssNeedleRegExpInsertsOptionalWhitespace($contentToInsertAround, $otherContent)
    {
        $result = self::getCssNeedleRegExp($otherContent . $contentToInsertAround . $otherContent);

        $quotedOtherContent = \preg_quote($otherContent, '/');
        $expectedResult = '/' . $quotedOtherContent . '\\s*+' . \preg_quote($contentToInsertAround, '/') . '\\s*+'
            . $quotedOtherContent . '/';

        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function getCssNeedleRegExpReplacesWhitespaceAtStartWithOptionalWhitespace()
    {
        $result = self::getCssNeedleRegExp(' a');

        self::assertSame('/\\s*+a/', $result);
    }

    /**
     * @return string[][]
     */
    public function styleTagDataProvider()
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
    public function getCssNeedleRegExpInsertsOptionalWhitespaceAfterStyleTag($needle)
    {
        $result = self::getCssNeedleRegExp($needle);

        self::assertSame('/\\<style\\>\\s*+a/', $result);
    }

    /**
     * @return string[][]
     */
    public function needleFoundDataProvider()
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
    public function needleNotFoundDataProvider()
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
    public function assertContainsCssPassesTestIfNeedleFound($needle, $haystack)
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
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssFailsTestIfNeedleNotFound($needle, $haystack)
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
    public function assertNotContainsCssPassesTestIfNeedleNotFound($needle, $haystack)
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
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function assertNotContainsCssFailsTestIfNeedleFound($needle, $haystack)
    {
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
    public function assertContainsCssCountPassesTestExpectingZeroIfNeedleNotFound($needle, $haystack)
    {
        self::assertContainsCssCount(0, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssCountFailsTestExpectingZeroIfNeedleFound($needle, $haystack)
    {
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
    public function assertContainsCssCountPassesTestExpectingOneIfNeedleFound($needle, $haystack)
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
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssCountFailsTestExpectingOneIfNeedleNotFound($needle, $haystack)
    {
        self::assertContainsCssCount(1, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssCountFailsTestExpectingOneIfNeedleFoundTwice($needle, $haystack)
    {
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
    public function assertContainsCssCountPassesTestExpectingTwoIfNeedleFoundTwice($needle, $haystack)
    {
        self::assertContainsCssCount(2, $needle, $haystack . $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleNotFoundDataProvider
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssCountFailsTestExpectingTwoIfNeedleNotFound($needle, $haystack)
    {
        self::assertContainsCssCount(2, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssCountFailsTestExpectingTwoIfNeedleFoundOnlyOnce($needle, $haystack)
    {
        self::assertContainsCssCount(2, $needle, $haystack);
    }
}
