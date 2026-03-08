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
     * @var RuleSet
     */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new RuleSet();
    }

    /**
     * @test
     */
    public function getSelectorsAsKeysByDefaultReturnsEmptyArray(): void
    {
        self::assertSame([], $this->subject->getSelectorsAsKeys());
    }

    /**
     * @test
     */
    public function setSelectorsAsKeysSetSelectorsAsKeys(): void
    {
        $selectorsAsKeys = ['foo' => 'bar'];

        $this->subject->setSelectorsAsKeys($selectorsAsKeys);

        self::assertSame($selectorsAsKeys, $this->subject->getSelectorsAsKeys());
    }

    /**
     * @test
     */
    public function addSelectorsAsKeysWithEmptyArrayKeepsSelectorsAsKeyUnchanged(): void
    {
        $selectorsAsKeys = ['foo' => 'bar'];
        $this->subject->setSelectorsAsKeys($selectorsAsKeys);

        $this->subject->addSelectorsAsKeys([]);

        self::assertSame($selectorsAsKeys, $this->subject->getSelectorsAsKeys());
    }

    /**
     * @test
     */
    public function addSelectorsAsKeysWithNewArrayKeysAddsThem(): void
    {
        $this->subject->setSelectorsAsKeys(['foo' => 'bar']);

        $this->subject->addSelectorsAsKeys(['good' => 'morning']);

        self::assertSame(
            [
                'foo' => 'bar',
                'good' => 'morning',
            ],
            $this->subject->getSelectorsAsKeys()
        );
    }

    /**
     * @test
     */
    public function addSelectorsAsKeysWithExistingArrayKeysKeepsExistingElements(): void
    {
        $existingSelectorsAsKeys = ['foo' => 'bar'];
        $this->subject->setSelectorsAsKeys($existingSelectorsAsKeys);

        $newSelectorsAsKeys = ['foo' => 'tea'];
        $this->subject->addSelectorsAsKeys($newSelectorsAsKeys);

        self::assertSame($existingSelectorsAsKeys, $this->subject->getSelectorsAsKeys());
    }

    /**
     * @test
     */
    public function getDeclarationsBlockInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getDeclarationsBlock());
    }

    /**
     * @test
     */
    public function setDeclarationsBlockSetsDeclarationsBlock(): void
    {
        $value = 'Club-Mate';
        $this->subject->setDeclarationsBlock($value);

        self::assertSame($value, $this->subject->getDeclarationsBlock());
    }
}
