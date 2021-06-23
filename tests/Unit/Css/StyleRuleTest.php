<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Css;

use Pelago\Emogrifier\Css\StyleRule;
use PHPUnit\Framework\TestCase;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\RuleSet\DeclarationBlock;

/**
 * @covers \Pelago\Emogrifier\Css\StyleRule
 */
final class StyleRuleTest extends TestCase
{
    /**
     * @return array<string, array{0: array<int, string>, 1: string}>
     */
    public function provideSelectors(): array
    {
        return [
            'single selector' => [['h1'], 'h1'],
            'two selectors' => [['h1', 'p'], 'h1, p'],
        ];
    }

    /**
     * @test
     *
     * @param array<int, string> $selectors
     *
     * @dataProvider provideSelectors
     */
    public function getSelectorsReturnsSelectorsProvidedToConstructor(array $selectors): void
    {
        $declarationBlock = new DeclarationBlock();
        $declarationBlock->setSelectors($selectors);
        $rule = new StyleRule($declarationBlock, '');

        self::assertSame($selectors, $rule->getSelectors());
    }

    /**
     * @test
     */
    public function hasAtLeastOneDeclarationForEmptyDeclarationBlockReturnsFalse(): void
    {
        $styleRule = new StyleRule(new DeclarationBlock(), '');

        self::assertFalse($styleRule->hasAtLeastOneDeclaration());
    }

    /**
     * @test
     */
    public function hasAtLeastOneDeclarationForDeclarationBlockWithOneRuleReturnsTrue(): void
    {
        $declarationBlock = new DeclarationBlock();
        $declarationBlock->addRule(new Rule('color: black;'));
        $styleRule = new StyleRule($declarationBlock, '');

        self::assertTrue($styleRule->hasAtLeastOneDeclaration());
    }

    /**
     * @return array<string, array{0: array<int, array{property: string, value: string}>, 1: string}>
     */
    public function provideDeclarations(): array
    {
        return [
            'no rules' => [[], ''],
            '1 rule' => [[['property' => 'color', 'value' => 'black']], 'color: black;'],
            '2 rules' => [
                [['property' => 'color', 'value' => 'black'], ['property' => 'border', 'value' => 'none']],
                'color: black; border: none;',
            ],
        ];
    }

    /**
     * @test
     *
     * @param array<int, array{property: string, value: string}> $declarations
     * @param string $expected
     *
     * @dataProvider provideDeclarations
     */
    public function getDeclarationAsTextReturnsConcatenatedDeclarationsFromRules(
        array $declarations,
        string $expected
    ): void {
        $declarationBlock = new DeclarationBlock();
        foreach ($declarations as $declaration) {
            $rule = new Rule($declaration['property']);
            $rule->setValue($declaration['value']);
            $declarationBlock->addRule($rule);
        }
        $styleRule = new StyleRule($declarationBlock, '');

        self::assertSame($expected, $styleRule->getDeclarationAsText());
    }

    /**
     * @test
     */
    public function getContainingAtRuleReturnsContainingAtRuleProvidedToConstructor(): void
    {
        $containingAtRule = '@media screen and (max-width: 480px)';
        $rule = new StyleRule(new DeclarationBlock(), $containingAtRule);

        self::assertSame($containingAtRule, $rule->getContainingAtRule());
    }

    /**
     * @test
     */
    public function getContainingAtRuleTrimsContainingAtRule(): void
    {
        $containingAtRule = ' @media screen and (max-width: 480px) ';
        $rule = new StyleRule(new DeclarationBlock(), $containingAtRule);

        self::assertSame(\trim($containingAtRule), $rule->getContainingAtRule());
    }

    /**
     * @test
     */
    public function containingAtRuleCanBeEmpty(): void
    {
        $containingAtRule = '';
        $rule = new StyleRule(new DeclarationBlock(), $containingAtRule);

        self::assertSame($containingAtRule, $rule->getContainingAtRule());
    }

    /**
     * @test
     */
    public function containingAtRuleByDefaultIsEmpty(): void
    {
        $rule = new StyleRule(new DeclarationBlock());

        self::assertSame('', $rule->getContainingAtRule());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function provideEmptyOrWhiteSpaceOnlyStrings(): array
    {
        return [
            'empty string' => [''],
            'whitespace: space' => [' '],
            'whitespace: tab' => ["\t"],
            'whitespace: CR' => ["\r"],
            'whitespace: LF' => ["\n"],
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideEmptyOrWhiteSpaceOnlyStrings
     */
    public function hasContainingAtRuleForEmptyContainingAtRuleReturnsFalse(string $containingAtRule): void
    {
        $rule = new StyleRule(new DeclarationBlock(), $containingAtRule);

        self::assertFalse($rule->hasContainingAtRule());
    }

    /**
     * @test
     */
    public function hasContainingAtRuleForNonEmptyContainingAtRuleReturnsTrue(): void
    {
        $rule = new StyleRule(new DeclarationBlock(), '@media all');

        self::assertTrue($rule->hasContainingAtRule());
    }
}
