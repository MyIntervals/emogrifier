<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Traits;

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
final class AssertCssTest extends TestCase
{
    use AssertCss;

    /**
     * @return string[][]
     */
    public function needleFoundDataProvider(): array
    {
        $cssStrings = [
            'unminified CSS' => 'html, body { color: green; }',
            'minified CSS' => 'html,body{color: green}',
            'CSS with extra spaces' => '  html  ,  body  {  color: green  ;  }',
            'CSS with linefeeds' => "\nhtml\n,\nbody\n{\ncolor: green\n;\n}",
            'CSS with Windows line endings' => "\r\nhtml\r\n,\r\nbody\r\n{\r\ncolor: green\r\n;\r\n}",
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
            'missing `;` after declaration where not optional' => [
                'body { color: green; font-size: 15px; }',
                "body { color: green\nfont-size: 15px; }",
            ],
            'extra `;` after declaration' => ['body { color: green; }', 'body { color: green;; }'],
            'spurious `;` after rule in at-rule' => [
                '@media print { body { color: green; } }',
                '@media print { body { color: green; }; }',
            ],
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
