<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Constraint;

use Pelago\Emogrifier\Tests\Support\Constraint\StringContainsCssCount;
use Pelago\Emogrifier\Tests\Support\Traits\CssDataProviders;
use Pelago\Emogrifier\Tests\Support\Traits\TestStringConstraint;
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

    protected function createSubject(): StringContainsCssCount
    {
        return new StringContainsCssCount(0, '');
    }
}
