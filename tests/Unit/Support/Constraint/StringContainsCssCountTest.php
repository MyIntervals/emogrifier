<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Constraint;

use Pelago\Emogrifier\Tests\Support\Constraint\StringContainsCssCount;
use Pelago\Emogrifier\Tests\Support\Traits\CssDataProviders;
use Pelago\Emogrifier\Tests\Support\Traits\TestStringConstraint;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Tests\Support\Constraint\StringContainsCss
 */
final class StringContainsCssCountTest extends TestCase
{
    use CssDataProviders;
    use TestStringConstraint;

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider provideCssNeedleNotFoundInHaystack
     */
    public function matchesHaystackNotContainingNeedleWithZeroCount(string $needle, string $haystack): void
    {
        $subject = new StringContainsCssCount(0, $needle);

        $result = $subject->evaluate($haystack, '', true);

        self::assertTrue($result);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider provideEquivalentCss
     * @dataProvider provideEquivalentCssInStyleTags
     * @dataProvider provideCssNeedleFoundInLargerHaystack
     */
    public function notMatchesHaystackContainingNeedleWithZeroCount(string $needle, string $haystack): void
    {
        $subject = new StringContainsCssCount(0, $needle);

        $result = $subject->evaluate($haystack, '', true);

        self::assertFalse($result);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider provideEquivalentCss
     * @dataProvider provideEquivalentCssInStyleTags
     * @dataProvider provideCssNeedleFoundInLargerHaystack
     */
    public function matchesHaystackContainingExactlyOneNeedleWithCountOfOne(string $needle, string $haystack): void
    {
        $subject = new StringContainsCssCount(1, $needle);

        $result = $subject->evaluate($haystack, '', true);

        self::assertTrue($result);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider provideCssNeedleNotFoundInHaystack
     */
    public function notMatchesHaystackNotContainingNeedleWithCountOfOne(string $needle, string $haystack): void
    {
        $subject = new StringContainsCssCount(1, $needle);

        $result = $subject->evaluate($haystack, '', true);

        self::assertFalse($result);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider provideEquivalentCss
     * @dataProvider provideEquivalentCssInStyleTags
     * @dataProvider provideCssNeedleFoundInLargerHaystack
     */
    public function notMatchesHaystackContainingNeedleTwiceWithCountOfOne(string $needle, string $haystack): void
    {
        $subject = new StringContainsCssCount(1, $needle);

        $result = $subject->evaluate($haystack . $haystack, '', true);

        self::assertFalse($result);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider provideEquivalentCss
     * @dataProvider provideEquivalentCssInStyleTags
     * @dataProvider provideCssNeedleFoundInLargerHaystack
     */
    public function matchesHaystackContainingNeedleTwiceWithCountOfTwo(string $needle, string $haystack): void
    {
        $subject = new StringContainsCssCount(2, $needle);

        $result = $subject->evaluate($haystack . $haystack, '', true);

        self::assertTrue($result);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider provideCssNeedleNotFoundInHaystack
     */
    public function notMatchesHaystackNotContainingNeedleWithCountOfTwo(string $needle, string $haystack): void
    {
        $subject = new StringContainsCssCount(2, $needle);

        $result = $subject->evaluate($haystack, '', true);

        self::assertFalse($result);
    }

    /**
     * @test
     *
     * @param string $needle
     * @param string $haystack
     *
     * @dataProvider provideEquivalentCss
     * @dataProvider provideEquivalentCssInStyleTags
     * @dataProvider provideCssNeedleFoundInLargerHaystack
     */
    public function notMatchesHaystackContainingExactlyOneNeedleWithCountOfTwo(string $needle, string $haystack): void
    {
        $subject = new StringContainsCssCount(2, $needle);

        $result = $subject->evaluate($haystack, '', true);

        self::assertFalse($result);
    }

    /**
     * @return Constraint
     */
    protected function createSubject(): Constraint
    {
        return new StringContainsCssCount(0, '');
    }
}
