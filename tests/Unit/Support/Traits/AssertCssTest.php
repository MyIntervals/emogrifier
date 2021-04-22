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
     */
    public function assertContainsCssCountPassesTestExpectingZeroIfNeedleNotFound(): void
    {
        self::assertContainsCssCount(0, 'a', 'b');
    }

    /**
     * @test
     */
    public function assertContainsCssCountFailsTestExpectingZeroIfNeedleFound(): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount(0, 'a', 'a');
    }

    /**
     * @test
     */
    public function assertContainsCssCountPassesTestExpectingOneIfNeedleFound(): void
    {
        self::assertContainsCssCount(1, 'a', 'a');
    }

    /**
     * @test
     */
    public function assertContainsCssCountFailsTestExpectingOneIfNeedleNotFound(): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount(1, 'a', 'b');
    }

    /**
     * @test
     */
    public function assertContainsCssCountFailsTestExpectingOneIfNeedleFoundTwice(): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount(1, 'a', 'a a');
    }

    /**
     * @test
     */
    public function assertContainsCssCountPassesTestExpectingTwoIfNeedleFoundTwice(): void
    {
        self::assertContainsCssCount(2, 'a', 'a a');
    }

    /**
     * @test
     */
    public function assertContainsCssCountFailsTestExpectingTwoIfNeedleNotFound(): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount(2, 'a', 'b');
    }

    /**
     * @test
     */
    public function assertContainsCssCountFailsTestExpectingTwoIfNeedleFoundOnlyOnce(): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount(2, 'a', 'a');
    }
}
