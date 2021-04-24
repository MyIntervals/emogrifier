<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Traits;

use Pelago\Emogrifier\Tests\Support\Constraint\IsEquivalentCss;
use Pelago\Emogrifier\Tests\Support\Constraint\StringContainsCss;
use Pelago\Emogrifier\Tests\Support\Constraint\StringContainsCssCount;
use PHPUnit\Framework\TestCase;

/**
 * Provides assertion methods for use with CSS content where whitespace may vary.
 *
 * @mixin TestCase
 */
trait AssertCss
{
    /**
     * Like `assertSame` but allows for addition or removal of some unnecessary whitespace in the CSS.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    private static function assertEquivalentCss(string $expected, string $actual, string $message = ''): void
    {
        $constraint = new IsEquivalentCss($expected);

        self::assertThat($actual, $constraint, $message);
    }

    /**
     * Like `assertNotSame` but allows for addition or removal of some unnecessary whitespace in the CSS.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    private static function assertNotEquivalentCss(string $expected, string $actual, string $message = ''): void
    {
        $constraint = self::logicalNot(new IsEquivalentCss($expected));

        self::assertThat($actual, $constraint, $message);
    }

    /**
     * Like `assertContains` but allows for removal of some unnecessary whitespace from the CSS.
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    private static function assertContainsCss(string $needle, string $haystack, string $message = ''): void
    {
        $constraint = new StringContainsCss($needle);

        self::assertThat($haystack, $constraint, $message);
    }

    /**
     * Like `assertNotContains` and also enforces the assertion with removal of some unnecessary whitespace from the
     * CSS.
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    private static function assertNotContainsCss(string $needle, string $haystack, string $message = ''): void
    {
        $constraint = self::logicalNot(new StringContainsCss($needle));

        static::assertThat($haystack, $constraint, $message);
    }

    /**
     * Asserts that a string of CSS occurs exactly a certain number of times in the result, allowing for removal of some
     * unnecessary whitespace.
     *
     * @param int $expectedCount
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    private static function assertContainsCssCount(
        int $expectedCount,
        string $needle,
        string $haystack,
        string $message = ''
    ): void {
        $constraint = new StringContainsCssCount($expectedCount, $needle);

        self::assertThat($haystack, $constraint, $message);
    }

    /**
     * @param string $css
     *
     * @return IsEquivalentCss
     */
    private static function isEquivalentCss(string $css): IsEquivalentCss
    {
        return new IsEquivalentCss($css);
    }

    /**
     * @param string $needle
     *
     * @return StringContainsCss
     */
    private static function stringContainsCss(string $needle): StringContainsCss
    {
        return new StringContainsCss($needle);
    }

    /**
     * @param int $expectedCount
     * @param string $needle
     *
     * @return StringContainsCssCount
     */
    private static function stringContainsCssCount(int $expectedCount, string $needle): StringContainsCssCount
    {
        return new StringContainsCssCount($expectedCount, $needle);
    }
}
