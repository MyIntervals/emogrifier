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
     * Chains on to {@see getCssRegularExpressionMatcher} for testing the protected method.
     *
     * @param string $css
     *
     * @return string
     */
    public static function getCssRegularExpressionMatcherForTesting(string $css): string
    {
        return self::getCssRegularExpressionMatcher($css);
    }
}
