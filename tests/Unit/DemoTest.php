<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
final class DemoTest extends TestCase
{
    /**
     * @var int
     */
    private $thing = 1;

    /**
     * @test
     */
    public function increaseToTwo(): void
    {
        self::assertSame(1, $this->thing);

        $this->thing = 2;
    }

    /**
     * @test
     */
    public function increaseToThree(): void
    {
        self::assertSame(1, $this->thing);

        $this->thing = 2;
    }

    /**
     * @return array<non-empty-string, array{0: positive-int}>
     */
    public static function integerDataProvider(): array
    {
        return [
            'four' => [4],
            'five' => [5],
        ];
    }

    /**
     * @test
     *
     * @dataProvider integerDataProvider
     */
    public function increaseWithDataProvider(int $value): void
    {
        self::assertSame(1, $this->thing);

        $this->thing = $value;
    }
}
