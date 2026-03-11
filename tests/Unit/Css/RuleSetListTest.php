<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Css;

use Pelago\Emogrifier\Css\RuleSet;
use Pelago\Emogrifier\Css\RuleSetList;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Css\RuleSetList
 */
final class RuleSetListTest extends TestCase
{
    /**
     * @return array<non-empty-string, array{0: string}>
     */
    public static function provideAtRule(): array
    {
        return [
            'none' => [''],
            '@media' => ['@media (min-width: 400px)'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideAtRule
     */
    public function getAtRuleReturnsAtRuleProvidedToConstructor(string $atRule): void
    {
        $subject = new RuleSetList($atRule);

        self::assertSame($atRule, $subject->getAtRule());
    }

    /**
     * @test
     */
    public function appendRuleSetAppendsRuleSet(): void
    {
        $subject = new RuleSetList('');

        $ruleSet1 = new RuleSet([], '');
        $subject->appendRuleSet($ruleSet1);

        self::assertSame([$ruleSet1], $subject->getRuleSets());

        $ruleSet2 = new RuleSet(['p'], 'need: coffee');
        $subject->appendRuleSet($ruleSet2);

        self::assertSame([$ruleSet1, $ruleSet2], $subject->getRuleSets());
    }

    /**
     * @test
     */
    public function getRuleSetsByDefaultReturnsEmptyList(): void
    {
        $subject = new RuleSetList('');

        self::assertSame([], $subject->getRuleSets());
    }

    /**
     * @test
     */
    public function getRuleSetsReturnsArrayWithRuleSetAppended(): void
    {
        $subject = new RuleSetList('');
        $ruleSet = new RuleSet([], '');
        $subject->appendRuleSet($ruleSet);

        $result = $subject->getRuleSets();

        self::assertSame([$ruleSet], $result);
    }
}
