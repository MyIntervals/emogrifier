<?php

namespace Pelago\Tests\Unit\Support\Traits;

use Pelago\Tests\Unit\Support\Traits\Fixtures\TestingAssertCss;

/**
 * Test case.
 */
class AssertCssTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function getCssNeedleRegExpEscapesAllSpecialCharacters()
    {
        $needle = '.\\+*?[^]$(){}=!<>|:-/';

        $result = TestingAssertCss::getCssNeedleRegExp($needle);

        static::assertSame(
            '/' . preg_quote($needle, '/') . '/',
            str_replace('\\s*+', '', $result)
        );
    }

    /**
     * @test
     */
    public function getCssNeedleRegExpNotEscapesNonSpecialCharacters()
    {
        $needle
            = implode('', range('a', 'z'))
            . implode('', range('A', 'Z'))
            . implode('', range('0 ', '9 '))
            . "\r\n\t `¬\"£%&_;'@#~,";

        $result = TestingAssertCss::getCssNeedleRegExp($needle);

        static::assertSame(
            '/' . $needle . '/',
            str_replace('\\s*+', '', $result)
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
        $result = TestingAssertCss::getCssNeedleRegExp(
            $otherContent . $contentToInsertAround . $otherContent
        );

        $quotedOtherContent = preg_quote($otherContent, '/');

        static::assertSame(
            '/' . $quotedOtherContent . '\\s*+' . preg_quote($contentToInsertAround, '/') . '\\s*+'
                . $quotedOtherContent . '/',
            $result
        );
    }

    /**
     * @return string[][]
     */
    public function needleFoundDataProvider()
    {
        $cssStrings = [
            'unminified CSS' => 'body { color: green; }',
            'minified CSS' => 'body{color: green;}',
            'CSS with extra spaces' => 'body  {  color: green;  }',
            'CSS with linefeeds' => "body\n{\ncolor: green;\n}",
            'CSS with Windows line endings' => "body\r\n{\r\ncolor: green;\r\n}",
        ];

        $datasets = [];
        foreach ($cssStrings as $needleDescription => $needle) {
            foreach ($cssStrings as $haystackDescription => $haystack) {
                $datasets[$needleDescription . ' in ' . $haystackDescription] = [$needle, $haystack];
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
        TestingAssertCss::assertContainsCss($needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleNotFoundDataProvider
     *
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssFailsTestIfNeedleNotFound($needle, $haystack)
    {
        TestingAssertCss::assertContainsCss($needle, $haystack);
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
        TestingAssertCss::assertNotContainsCss($needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     *
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function assertNotContainsCssFailsTestIfNeedleFound($needle, $haystack)
    {
        TestingAssertCss::assertNotContainsCss($needle, $haystack);
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
        TestingAssertCss::assertContainsCssCount(0, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     *
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssCountFailsTestExpectingZeroIfNeedleFound($needle, $haystack)
    {
        TestingAssertCss::assertContainsCssCount(0, $needle, $haystack);
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
        TestingAssertCss::assertContainsCssCount(1, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleNotFoundDataProvider
     *
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssCountFailsTestExpectingOneIfNeedleNotFound($needle, $haystack)
    {
        TestingAssertCss::assertContainsCssCount(1, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     *
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssCountFailsTestExpectingOneIfNeedleFoundTwice($needle, $haystack)
    {
        TestingAssertCss::assertContainsCssCount(1, $needle, $haystack . $haystack);
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
        TestingAssertCss::assertContainsCssCount(2, $needle, $haystack . $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleNotFoundDataProvider
     *
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssCountFailsTestExpectingTwoIfNeedleNotFound($needle, $haystack)
    {
        TestingAssertCss::assertContainsCssCount(2, $needle, $haystack);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider needleFoundDataProvider
     *
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContainsCssCountFailsTestExpectingTwoIfNeedleFoundOnlyOnce($needle, $haystack)
    {
        TestingAssertCss::assertContainsCssCount(2, $needle, $haystack);
    }
}
