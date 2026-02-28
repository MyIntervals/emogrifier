<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Css;

use Pelago\Emogrifier\Css\StyleRule;
use PHPUnit\Framework\TestCase;
use Sabberworm\CSS\Property\Declaration;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\Value\RuleValueList;

/**
 * @covers \Pelago\Emogrifier\Css\StyleRule
 */
final class StyleRuleTest extends TestCase
{
    /**
     * @return array<non-empty-string, array{0: list<non-empty-string>}>
     */
    public function provideSelectors(): array
    {
        return [
            'single selector' => [['h1']],
            'two selectors' => [['h1', 'p']],
        ];
    }

    /**
     * @test
     *
     * @param list<non-empty-string> $selectors
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
        $declarationBlock->addDeclaration(new Declaration('color: black;'));
        $styleRule = new StyleRule($declarationBlock, '');

        self::assertTrue($styleRule->hasAtLeastOneDeclaration());
    }

    /**
     * @return array<non-empty-string, array{
     *             0: list<array{property: non-empty-string, value: non-empty-string}>,
     *             1: string,
     *         }>
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
     * @param list<array{property: non-empty-string, value: non-empty-string}> $declarationsData
     *
     * @dataProvider provideDeclarations
     */
    public function getDeclarationsAsTextReturnsConcatenatedDeclarationsFromRules(
        array $declarationsData,
        string $expected
    ): void {
        $declarationBlock = new DeclarationBlock();
        foreach ($declarationsData as $declarationData) {
            $declaration = new Declaration($declarationData['property']);
            $value = new RuleValueList();
            $value->addListComponent($declarationData['value']);
            $declaration->setValue($value);
            $declarationBlock->addDeclaration($declaration);
        }
        $styleRule = new StyleRule($declarationBlock, '');

        self::assertSame($expected, $styleRule->getDeclarationsAsText());
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
     * @return array<non-empty-string, array{0: string}>
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
