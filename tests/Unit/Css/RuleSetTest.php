<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Css;

use Pelago\Emogrifier\Css\RuleSet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Css\RuleSet
 */
final class RuleSetTest extends TestCase
{
    /**
     * @test
     */
    public function getSelectorsAsKeysReturnsSelectorsProvidedToConstructor(): void
    {
        $selectorsAsKeys = ['foo' => 'bar'];

        $subject = new RuleSet($selectorsAsKeys, 'foo');

        self::assertSame($selectorsAsKeys, $subject->getSelectorsAsKeys());
    }

    /**
     * @test
     */
    public function setSelectorsAsKeysSetSelectorsAsKeys(): void
    {
        $subject = new RuleSet([], 'foo');

        $selectorsAsKeys = ['foo' => 'bar'];
        $subject->setSelectorsAsKeys($selectorsAsKeys);

        self::assertSame($selectorsAsKeys, $subject->getSelectorsAsKeys());
    }

    /**
     * @test
     */
    public function addSelectorsAsKeysWithEmptyArrayKeepsSelectorsAsKeyUnchanged(): void
    {
        $selectorsAsKeys = ['foo' => 'bar'];
        $subject = new RuleSet($selectorsAsKeys, 'foo');

        $subject->addSelectorsAsKeys([]);

        self::assertSame($selectorsAsKeys, $subject->getSelectorsAsKeys());
    }

    /**
     * @test
     */
    public function addSelectorsAsKeysWithNewArrayKeysAddsThem(): void
    {
        $subject = new RuleSet(['foo' => 'bar'], 'foo');

        $subject->addSelectorsAsKeys(['good' => 'morning']);

        self::assertSame(
            [
                'foo' => 'bar',
                'good' => 'morning',
            ],
            $subject->getSelectorsAsKeys()
        );
    }

    /**
     * @test
     */
    public function addSelectorsAsKeysWithExistingArrayKeysKeepsExistingElements(): void
    {
        $existingSelectorsAsKeys = ['foo' => 'bar'];
        $subject = new RuleSet($existingSelectorsAsKeys, 'foo');

        $newSelectorsAsKeys = ['foo' => 'tea'];
        $subject->addSelectorsAsKeys($newSelectorsAsKeys);

        self::assertSame($existingSelectorsAsKeys, $subject->getSelectorsAsKeys());
    }

    /**
     * @test
     */
    public function getDeclarationBlockReturnsDeclarationBlockProvidedToConstructor(): void
    {
        $value = 'Club-Mate';

        $subject = new RuleSet([], $value);

        self::assertSame($value, $subject->getDeclarationBlock());
    }

    /**
     * @test
     */
    public function setDeclarationBlockSetsDeclarationBlock(): void
    {
        $subject = new RuleSet(['foo' => 'bar'], 'foo');

        $value = 'Club-Mate';
        $subject->setDeclarationBlock($value);

        self::assertSame($value, $subject->getDeclarationBlock());
    }
}
