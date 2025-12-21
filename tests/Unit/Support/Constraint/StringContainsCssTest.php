<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Constraint;

use Pelago\Emogrifier\Tests\Support\Constraint\StringContainsCss;
use Pelago\Emogrifier\Tests\Support\Traits\CssDataProviders;
use Pelago\Emogrifier\Tests\Support\Traits\TestStringConstraint;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Tests\Support\Constraint\StringContainsCss
 */
final class StringContainsCssTest extends TestCase
{
    use CssDataProviders;
    use TestStringConstraint;

    protected function setUp(): void
    {
        $this->subject = new StringContainsCss('');
    }

    /**
     * @test
     *
     * @param non-empty-string $needle
     * @param non-empty-string $haystack
     *
     * @dataProvider provideEquivalentCss
     * @dataProvider provideEquivalentCssInStyleTags
     * @dataProvider provideCssNeedleFoundInLargerHaystack
     */
    public function matchesHaystackContainingNeedle(string $needle, string $haystack): void
    {
        $subject = new StringContainsCss($needle);

        $result = $subject->evaluate($haystack, '', true);

        self::assertTrue($result);
    }

    /**
     * @test
     *
     * @param non-empty-string $needle
     * @param non-empty-string $haystack
     *
     * @dataProvider provideCssNeedleNotFoundInHaystack
     */
    public function notMatchesHaystackNotContainingNeedle(string $needle, string $haystack): void
    {
        $subject = new StringContainsCss($needle);

        $result = $subject->evaluate($haystack, '', true);

        self::assertFalse($result);
    }
}
