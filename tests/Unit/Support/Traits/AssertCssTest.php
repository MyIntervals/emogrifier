<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Support\Traits;

use Pelago\Emogrifier\Tests\Support\Traits\AssertCss;
use PHPUnit\Framework\Constraint\Constraint;
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
    public function assertEquivalentCssPassesTestWithMatchingCss(): void
    {
        self::assertEquivalentCss('a', 'a');
    }

    /**
     * @test
     */
    public function assertEquivalentCssFailsTestWithNonMatchingCss(): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertEquivalentCss('a', 'b');
    }

    /**
     * @test
     */
    public function assertNotEquivalentCssPassesTestWithNonMatchingCss(): void
    {
        self::assertNotEquivalentCss('a', 'b');
    }

    /**
     * @test
     */
    public function assertNotEquivalentCssFailsTestWithMatchingCss(): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertNotEquivalentCss('a', 'a');
    }

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
     * @param int<0, max> $count
     * @param non-empty-string $needle
     * @param non-empty-string $haystack
     *
     * @dataProvider providePassingCssCountData
     */
    public function assertContainsCssCountPassesTestWhenExpected(int $count, string $needle, string $haystack): void
    {
        self::assertContainsCssCount($count, $needle, $haystack);
    }

    /**
     * @return array<non-empty-string, array{0: int<0, max>, 1: non-empty-string, 2: non-empty-string}>
     */
    public function providePassingCssCountData(): array
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
     * @param int<0, max> $count
     * @param non-empty-string $needle
     * @param non-empty-string $haystack
     *
     * @dataProvider provideFailingCssCountData
     */
    public function assertContainsCssCountFailsTestWhenExpected(int $count, string $needle, string $haystack): void
    {
        $this->expectException(ExpectationFailedException::class);

        self::assertContainsCssCount($count, $needle, $haystack);
    }

    /**
     * @return array<non-empty-string, array{0: int<0, max>, 1: non-empty-string, 2: non-empty-string}>
     */
    public function provideFailingCssCountData(): array
    {
        return [
            'expecting none but finding some' => [0, 'a', 'a'],
            'expecting 1 but finding none' => [1, 'a', 'b'],
            'expecting 1 but finding 2' => [1, 'a', 'a a'],
            'expecting 2 but finding none' => [2, 'a', 'b'],
            'expecting 2 but finding 1' => [2, 'a', 'a'],
        ];
    }

    /**
     * @test
     */
    public function isEquivalentCssReturnsConstraint(): void
    {
        $subject = self::isEquivalentCss('');

        self::assertInstanceOf(Constraint::class, $subject);
    }

    /**
     * @test
     */
    public function isEquivalentCssReturnsConstraintMatchingEquivalentCss(): void
    {
        $subject = self::isEquivalentCss('a');

        $result = $subject->evaluate('a', '', true);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function isEquivalentCssReturnsConstraintNotMatchingNonEquivalentCss(): void
    {
        $subject = self::isEquivalentCss('a');

        $result = $subject->evaluate('b', '', true);

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function stringContainsCssReturnsConstraint(): void
    {
        $subject = self::stringContainsCss('foo');

        self::assertInstanceOf(Constraint::class, $subject);
    }

    /**
     * @test
     */
    public function stringContainsCssReturnsConstraintMatchingIfNeedleFound(): void
    {
        $subject = self::stringContainsCss('a');

        $result = $subject->evaluate('a', '', true);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function stringContainsCssReturnsConstraintNotMatchingIfNeedleNotFound(): void
    {
        $subject = self::stringContainsCss('a');

        $result = $subject->evaluate('b', '', true);

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function stringContainsCssCountReturnsConstraint(): void
    {
        $subject = self::stringContainsCssCount(0, 'foo');

        self::assertInstanceOf(Constraint::class, $subject);
    }

    /**
     * @test
     *
     * @param int<0, max> $count
     * @param non-empty-string $needle
     * @param non-empty-string $haystack
     *
     * @dataProvider providePassingCssCountData
     */
    public function stringContainsCssCountReturnsConstraintMatchingExpectedNumberOfNeedles(
        int $count,
        string $needle,
        string $haystack
    ): void {
        $subject = self::stringContainsCssCount($count, $needle);

        $result = $subject->evaluate($haystack, '', true);

        self::assertTrue($result);
    }

    /**
     * @test
     *
     * @param int<0, max> $count
     * @param non-empty-string $needle
     * @param non-empty-string $haystack
     *
     * @dataProvider provideFailingCssCountData
     */
    public function stringContainsCssCountReturnsConstraintNotMatchingDifferentNumberOfNeedles(
        int $count,
        string $needle,
        string $haystack
    ): void {
        $subject = self::stringContainsCssCount($count, $needle);

        $result = $subject->evaluate($haystack, '', true);

        self::assertFalse($result);
    }
}
