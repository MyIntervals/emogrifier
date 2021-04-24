<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Traits;

use Pelago\Emogrifier\Tests\Support\Traits\AssertCss;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Tests\Support\Traits\AssertCss
 */
final class AssertCssTest extends TestCase
{
    use AssertCss;

    /**
     * @test
     */
    public function assertContainsCssPassesTestIfNeedleFound(): void
    {
        self::assertContainsCss('a', 'a');
    }

    /**
     * @test
     */
    public function assertContainsCssFailsTestIfNeedleNotFound(): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCss('a', 'b');
    }

    /**
     * @test
     */
    public function assertNotContainsCssPassesTestIfNeedleNotFound(): void
    {
        self::assertNotContainsCss('a', 'b');
    }

    /**
     * @test
     */
    public function assertNotContainsCssFailsTestIfNeedleFound(): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertNotContainsCss('a', 'a');
    }

    /**
     * @test
     *
     * @dataProvider providePassingCssCountData
     */
    public function assertContainsCssCountPassesTestWhenExpected(int $count, string $needle, string $haystack): void
    {
        self::assertContainsCssCount($count, $needle, $haystack);
    }

    /**
     * @return array<string, array{0: int, 1: string, 2: string}>
     */
    public function providePassingCssCountData()
    {
        return [
            'not finding needle when asked not to' => [0, 'a', 'b'],
            'finding exactly 1 needles' => [1, 'a', 'a'],
            'finding exactly 2 needles' => [2, 'a', 'a a'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideFailingCssCountData
     */
    public function assertContainsCssCountFailsTestWhenExpected(int $count, string $needle, string $haystack): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount($count, $needle, $haystack);
    }

    /**
     * @return array<string, array{0: int, 1: string, 2: string}>
     */
    public function provideFailingCssCountData()
    {
        return [
            'expecting none but finding some' => [0, 'a', 'a'],
            'expecting 1 but finding none' => [1, 'a', 'b'],
            'expecting 1 but finding 2' => [1, 'a', 'a a'],
            'expecting 2 but finding none' => [2, 'a', 'b'],
            'expecting 2 but finding 1' => [2, 'a', 'a'],
        ];
    }
}
