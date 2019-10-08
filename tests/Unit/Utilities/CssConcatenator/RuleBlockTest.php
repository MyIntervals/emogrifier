<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Utilities\CssConcatenator;

use Pelago\Emogrifier\Utilities\CssConcatenator\RuleBlock;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @covers \Pelago\Emogrifier\Utilities\CssConcatenator\RuleBlock
 *
 * @author Oliver Klee <github@oliverklee.de>
 */
class RuleBlockTest extends TestCase
{
    /**
     * @test
     *
     * @doesNotPerformAssertions
     */
    public function allConstructorArgumentsCanBeEmpty()
    {
        new RuleBlock([], '');
    }

    /**
     * @test
     */
    public function getSelectorsReturnsSelectorsFromConstructor()
    {
        $selectors = ['h1', '.important'];
        $subject = new RuleBlock($selectors, '');

        $result = $subject->getSelectors();

        self::assertSame($selectors, $result);
    }

    /**
     * @test
     */
    public function getSelectorsAsKeysReturnsSelectorsFromConstructor()
    {
        $selectors = ['h1', '.important'];
        $subject = new RuleBlock($selectors, '');

        $result = $subject->getSelectorsAsKeys();

        $selectorsAsKeys = \array_flip($selectors);
        self::assertSame($selectorsAsKeys, $result);
    }

    /**
     * @test
     */
    public function getDeclarationsBlockReturnsDeclarationsBlockFromConstructor()
    {
        $declarationsBlock = 'margin-top: 0.5em; padding: 0';
        $subject = new RuleBlock([], $declarationsBlock);

        $result = $subject->getDeclarationsBlock();

        self::assertSame($declarationsBlock, $result);
    }

    /**
     * @test
     */
    public function getCssForEmptyDataReturnsEmptyCss()
    {
        $subject = new RuleBlock([], '');

        $result = $subject->getCss();

        self::assertSame('{}', $result);
    }

    /**
     * @test
     */
    public function getCssReturnsDeclarationsBlock()
    {
        $declarationsBlock = 'margin-top: 0.5em; padding: 0';
        $subject = new RuleBlock([], $declarationsBlock);

        $result = $subject->getCss();

        self::assertSame('{' . $declarationsBlock . '}', $result);
    }

    /**
     * @return string[][][]
     */
    public function selectorDataProvider(): array
    {
        return [
            'no selectors' => [[]],
            'one selector' => [['p']],
            'multiple selectors' => [['p', 'h3']],
        ];
    }

    /**
     * @test
     *
     * @param string[] $selectors
     *
     * @dataProvider selectorDataProvider
     */
    public function getCssReturnsCommaSeparatedSelectors(array $selectors)
    {
        $subject = new RuleBlock($selectors, '');

        $result = $subject->getCss();

        self::assertSame(\implode(',', $selectors) . '{}', $result);
    }

    /**
     * @test
     */
    public function getCssCombinesSelectorsAndDeclarationsBlock()
    {
        $selectors = ['h1', 'h2'];
        $declarationsBlock = 'margin-top: 0.5em; padding: 0';
        $subject = new RuleBlock($selectors, $declarationsBlock);

        $result = $subject->getCss();

        $expectedResult = 'h1,h2{' . $declarationsBlock . '}';
        self::assertSame($expectedResult, $result);
    }

    /**
     * @return string[][][]
     */
    public function equivalentSelectorsDataProvider(): array
    {
        return [
            'both empty' => [[], []],
            'both with the same one selector' => [['p'], ['p']],
            'both with the same two selectors in the same order' => [['p', 'div'], ['p', 'div']],
            'both with the same two selectors in a different order' => [['p', 'div'], ['div', 'p']],
        ];
    }

    /**
     * @test
     *
     * @param string[] $ours
     * @param string[] $theirs
     *
     * @dataProvider equivalentSelectorsDataProvider
     */
    public function hasEquivalentSelectorsForEquivalentSelectorsReturnsTrue(array $ours, array $theirs)
    {
        $ourRuleBlock = new RuleBlock($ours, 'color: #000;');
        $theirsRuleBlock = new RuleBlock($theirs, 'color: #000;');

        self::assertTrue($ourRuleBlock->hasEquivalentSelectors($theirsRuleBlock));
    }

    /**
     * @return string[][][]
     */
    public function notEquivalentSelectorsDataProvider(): array
    {
        return [
            'first empty, second not empty' => [[], ['p']],
            'first not empty, second empty' => [['p'], []],
            'different selectors, one each' => [['p'], ['h']],
        ];
    }

    /**
     * @test
     *
     * @param string[] $ours
     * @param string[] $theirs
     *
     * @dataProvider notEquivalentSelectorsDataProvider
     */
    public function hasEquivalentSelectorsForNotEquivalentSelectorsReturnsFalse(array $ours, array $theirs)
    {
        $ourRuleBlock = new RuleBlock($ours, 'color: #000;');
        $theirsRuleBlock = new RuleBlock($theirs, 'color: #000;');

        self::assertFalse($ourRuleBlock->hasEquivalentSelectors($theirsRuleBlock));
    }

    // TODO:
    // - convertDeclarationsBlocks to array internally
    // - add addDeclarationsBlocks
    // - add addSelectors
    // - make the getters and setters private
    // - add mergeMaybe (see GitHub comment)
}
