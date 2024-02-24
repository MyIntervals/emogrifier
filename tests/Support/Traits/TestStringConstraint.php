<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Traits;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

/**
 * Adds common tests to a test case for a `Constraint` which is expected to only evaluate against strings.
 *
 * @mixin TestCase
 */
trait TestStringConstraint
{
    /**
     * @return mixed[][]
     */
    public function provideNonString(): array
    {
        return [
            'bool' => [false],
            'int' => [0],
            'float' => [0.0],
            'array' => [[]],
            'object' => [(object)[]],
            'resource' => [\fopen('php://temp', 'r')],
            'callable' => [
                static function (): void {},
            ],
        ];
    }

    /**
     * @test
     *
     * @param mixed $nonString
     *
     * @dataProvider provideNonString
     */
    public function evaluatesFalseForNonString($nonString): void
    {
        $constraint = $this->createSubject();

        $result = $constraint->evaluate($nonString, '', true);

        self::assertFalse($result);
    }

    /**
     * This is a placeholder for a method that should be implemented by the class using this trait.
     *
     * @throws \Exception
     */
    protected function createSubject(): Constraint
    {
        throw new \Exception('`createSubject` method must be re-implemented');
    }
}
