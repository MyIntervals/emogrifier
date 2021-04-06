<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Constraint\Fixtures;

use Pelago\Emogrifier\Tests\Support\Constraint\CssConstraint;

/**
 * Extends the `CssConstraint` class to provide indirect access to protected method(s) for testing.
 */
abstract class TestingCssConstraint extends CssConstraint
{
    /**
     * Chains on to `getCssNeedleRegularExpressionPattern` for testing the protected method.
     *
     * @param string $needle Needle that would be used with `assertContains` or `assertNotContains`.
     *
     * @return string Needle to use with `assertRegExp` or `assertNotRegExp` instead.
     */
    public static function getCssNeedleRegularExpressionPatternForTesting(string $needle): string
    {
        return self::getCssNeedleRegularExpressionPattern($needle);
    }
}
