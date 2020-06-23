<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Traits;

use Pelago\Emogrifier\Tests\Support\Constraint\StringContainsCss;
use Pelago\Emogrifier\Tests\Support\Constraint\StringContainsCssCount;

/**
 * Provides assertion methods for use with CSS content where whitespace may vary.
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 */
trait AssertCss
{
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
     * @param string $needle
     *
     * @return StringContainsCss
     */
    private static function stringContainsCss(string $needle): StringContainsCss
    {
        return new StringContainsCss($needle);
    }
}
