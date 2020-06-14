<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Utilities;

use Pelago\Emogrifier\Utilities\ArrayIntersector;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @covers \Pelago\Emogrifier\Utilities\ArrayIntersector
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 */
class ArrayIntersectorTest extends TestCase
{
    /**
     * @return (int|string)[][][]
     */
    public function arraysDataProvider(): array
    {
        return [
            'empty arrays' => [
                [],
                [],
            ],
            'empty array & 1-value array' => [
                [],
                [1],
            ],
            'empty array & 2-value array' => [
                [],
                [1, 2],
            ],
            '1-value array & empty array' => [
                [1],
                [],
            ],
            '2-value array & empty array' => [
                [1, 2],
                [],
            ],
            'different 1-value arrays' => [
                [1],
                [2],
            ],
            '1-value array & 2-value array with different values' => [
                [1],
                [2, 3],
            ],
            '2-value array & 1-value array with different values' => [
                [1, 2],
                [3],
            ],
            '2-value arrays with different values' => [
                [1, 2],
                [3, 4],
            ],
            'identical 1-value arrays' => [
                [1],
                [1],
            ],
            'identical 2-value arrays' => [
                [1, 2],
                [1, 2],
            ],
            '2-value array & its reverse' => [
                [1, 2],
                [2, 1],
            ],
            '1-value array & 2-value superset' => [
                [1],
                [1, 2],
            ],
            '2-value array & 1-value subset' => [
                [1, 2],
                [1],
            ],
            '2-value arrays with 1 common value' => [
                [1, 2],
                [2, 3],
            ],
            'arrays with string values' => [
                ['one', 'two'],
                ['two', 'three'],
            ],
            'arrays with mixed values' => [
                ['one', 2],
                [2, 'three'],
            ],
            'associative arrays' => [
                ['one' => 1, 'two' => 2],
                ['foo' => 2, 'bar' => 3],
            ],
            'associative arrays with string values' => [
                ['one' => 'one', 'two' => 'two'],
                ['foo' => 'two', 'bar' => 'three'],
            ],
            'mixed numeric/associative arrays' => [
                ['one' => 1, 2],
                ['foo' => 2, 3],
            ],
            // The following datasets focus more on preserving keys and order:
            'identical 2-value arrays: numeric array with sparse keys' => [
                [2 => 1, 4 => 2],
                [2 => 1, 4 => 2],
            ],
            'identical 2-value arrays: numeric array with keys in reverse order' => [
                [1 => 1, 0 => 2],
                [1 => 1, 0 => 2],
            ],
            'identical 2-value arrays: numeric array with elements in reverse numeric order' => [
                [2, 1],
                [2, 1],
            ],
            'identical 2-value arrays: associative array' => [
                ['one' => 1, 'two' => 2],
                ['one' => 1, 'two' => 2],
            ],
            '2-value associative arrays with same values but different keys' => [
                ['one' => 1, 'two' => 2],
                ['foo' => 1, 'bar' => 2],
            ],
            '3-value associative arrays with 2 common elements' => [
                ['one' => 1, 'two' => 2, 'three' => 3],
                ['foo' => 1, 'bar' => 3, 'baz' => 5],
            ],
            '3-value associative arrays with 2 common elements in reverse order' => [
                ['one' => 1, 'two' => 2, 'three' => 3],
                ['foo' => 5, 'bar' => 3, 'baz' => 1],
            ],
        ];
    }

    /**
     * @test
     *
     * @param (int|string)[] $array1
     * @param (int|string)[] $array2
     *
     * @dataProvider arraysDataProvider
     */
    public function computesIntersection(array $array1, array $array2): void
    {
        $expectedResult = \array_intersect($array1, $array2);

        $subject = new ArrayIntersector($array2);
        $result = $subject->intersectWith($array1);

        self::assertSame($expectedResult, $result);
    }
}
