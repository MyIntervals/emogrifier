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
    public function getSelectorsReturnsSelectorsProvidedToConstructor(): void
    {
        $selectors = ['foo'];

        $subject = new RuleSet($selectors, 'foo');

        self::assertSame($selectors, $subject->getSelectors());
    }

    /**
     * @test
     */
    public function addSelectorsWithEmptyArrayKeepsSelectorsUnchanged(): void
    {
        $selectors = ['foo'];
        $subject = new RuleSet($selectors, 'foo');

        $subject->addSelectors([]);

        self::assertSame($selectors, $subject->getSelectors());
    }

    /**
     * @test
     */
    public function addSelectorsWithNewSelectorsAddsThem(): void
    {
        $subject = new RuleSet(['foo'], 'foo');

        $subject->addSelectors(['good']);

        self::assertSame(['foo', 'good'], $subject->getSelectors());
    }

    /**
     * @test
     */
    public function addSelectorsWithExistingSelectorsKeepsExistingElements(): void
    {
        $existingSelectors = ['foo'];
        $subject = new RuleSet($existingSelectors, 'foo');

        $newSelectors = ['foo'];
        $subject->addSelectors($newSelectors);

        self::assertSame($existingSelectors, $subject->getSelectors());
    }

    /**
     * @return array<non-empty-string, array{0: list<non-empty-string>, 1: list<non-empty-string>}>
     */
    public static function provideEquivalentSelectors(): array
    {
        return [
            'no selectors' => [[], []],
            'one selector' => [['p'], ['p']],
            'two selectors in same order' => [['h1', 'p'], ['h1', 'p']],
            'two selectors in different order' => [['h1', 'p'], ['p', 'h1']],
        ];
    }

    /**
     * @test
     *
     * @param list<non-empty-string> $selectors1
     * @param list<non-empty-string> $selectors2
     *
     * @dataProvider provideEquivalentSelectors
     */
    public function hasEquivalentSelectorsReturnsTrueForEquivalentSelectors(array $selectors1, array $selectors2): void
    {
        $subject = new RuleSet($selectors1, 'foo');

        self::assertTrue($subject->hasEquivalentSelectors($selectors2));
    }

    /**
     * @return array<non-empty-string, array{0: list<non-empty-string>, 1: list<non-empty-string>}>
     */
    public static function provideNonEquivalentSelectors(): array
    {
        return [
            'no selectors and one selector' => [[], ['p']],
            'one selector and no selectors' => [['p'], []],
            'one selector and two selectors including a match' => [['p'], ['h1', 'p']],
            'two selectors and one selector including a match' => [['h1', 'p'], ['p']],
            'one selector and one different selector' => [['h1'], ['p']],
            'two selectors and two selectors one of which is different' => [['h1', 'p'], ['h2', 'p']],
        ];
    }

    /**
     * @test
     *
     * @param list<non-empty-string> $selectors1
     * @param list<non-empty-string> $selectors2
     *
     * @dataProvider provideNonEquivalentSelectors
     */
    public function hasEquivalentSelectorsReturnsFalseForNonEquivalentSelectors(
        array $selectors1,
        array $selectors2
    ): void {
        $subject = new RuleSet($selectors1, 'foo');

        self::assertFalse($subject->hasEquivalentSelectors($selectors2));
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
        $subject = new RuleSet(['foo'], 'foo');

        $value = 'Club-Mate';
        $subject->setDeclarationBlock($value);

        self::assertSame($value, $subject->getDeclarationBlock());
    }
}
