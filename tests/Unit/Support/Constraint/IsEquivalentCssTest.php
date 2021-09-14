<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Constraint;

use Pelago\Emogrifier\Tests\Support\Constraint\IsEquivalentCss;
use Pelago\Emogrifier\Tests\Support\Traits\CssDataProviders;
use Pelago\Emogrifier\Tests\Support\Traits\TestStringConstraint;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Tests\Support\Constraint\IsEquivalentCss
 */
final class IsEquivalentCssTest extends TestCase
{
    use CssDataProviders;

    use TestStringConstraint;

    /**
     * @test
     *
     * @param string $css
     * @param string $otherCss
     *
     * @dataProvider provideEquivalentCss
     */
    public function matchesEquivalentCss(string $css, string $otherCss): void
    {
        $subject = new IsEquivalentCss($css);

        $result = $subject->evaluate($otherCss, '', true);

        self::assertTrue($result);
    }

    /**
     * @test
     *
     * @param string $css
     * @param string $otherCss
     *
     * @dataProvider provideNonEquivalentCss
     */
    public function notMatchesNonEquivalentCss(string $css, string $otherCss): void
    {
        $subject = new IsEquivalentCss($css);

        $result = $subject->evaluate($otherCss, '', true);

        self::assertFalse($result);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function provideNonEquivalentCss(): array
    {
        $datasets = $this->provideCssNeedleNotFoundInHaystack() + $this->provideCssNeedleFoundInLargerHaystack();

        $transposedDatasets = \array_map(
            /**
             * @param array{0: string, 1: string} $dataset
             *
             * @return array{0: string, 1: string}
             */
            static function (array $dataset): array {
                return [$dataset[1], $dataset[0]];
            },
            static::arrayMapKeys(
                static function (string $description): string {
                    return $description . ' (transposed)';
                },
                $datasets
            )
        );

        return $datasets + $transposedDatasets;
    }

    /**
     * @return Constraint
     */
    protected function createSubject(): Constraint
    {
        return new IsEquivalentCss('');
    }
}
