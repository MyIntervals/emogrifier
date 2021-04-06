<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Constraint;

use Pelago\Emogrifier\Tests\Support\Constraint\StringContainsCssCount;
use Pelago\Emogrifier\Tests\Support\Traits\TestStringConstraint;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

/**
 * Note that the majority of the functionality is tested indirectly via
 * {@see \Pelago\Emogrifier\Tests\Unit\Support\Traits\AssertCssTest}; those tests are not repeated here.
 *
 * @covers \Pelago\Emogrifier\Tests\Support\Constraint\StringContainsCss
 */
final class StringContainsCssCountTest extends TestCase
{
    use TestStringConstraint;

    /**
     * @return Constraint
     */
    protected function createSubject(): Constraint
    {
        return new StringContainsCssCount(0, '');
    }
}
