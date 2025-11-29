<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Traits;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

use function Safe\fopen;

/**
 * Adds common tests to a test case for a `Constraint` which is expected to only evaluate against strings.
 *
 * @mixin TestCase
 *
 * @phpstan-type NonString bool|int|float|array{}|object|resource|callable
 */
trait TestStringConstraint
{
    /**
     * @var Constraint
     */
    private $subject;

    /**
     * @return array<non-empty-string, array{0: NonString}>
     */
    public function provideNonString(): array
    {
        return [
            'bool' => [false],
            'int' => [0],
            'float' => [0.0],
            'array' => [[]],
            'object' => [(object) []],
            'resource' => [fopen('php://temp', 'r')],
            'callable' => [
                static function (): void {},
            ],
        ];
    }

    /**
     * @test
     *
     * @param NonString $nonString
     *
     * @dataProvider provideNonString
     */
    public function evaluatesFalseForNonString($nonString): void
    {
        $result = $this->subject->evaluate($nonString, '', true);

        self::assertFalse($result);
    }
}
