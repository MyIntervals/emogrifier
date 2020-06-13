<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Utilities;

use Pelago\Emogrifier\Utilities\CssConcatenator;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @covers \Pelago\Emogrifier\Utilities\CssConcatenator
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 */
class CssConcatenatorTest extends TestCase
{
    /**
     * @var CssConcatenator
     */
    private $subject = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->subject = new CssConcatenator();
    }

    /**
     * @test
     */
    public function getCssInitiallyReturnsEmptyString(): void
    {
        $result = $this->subject->getCss();

        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function appendSetsFirstRule(): void
    {
        $this->subject->append(['p'], 'color: green;');

        $result = $this->subject->getCss();

        self::assertSame('p{color: green;}', $result);
    }

    /**
     * @test
     */
    public function appendWithMediaQuerySetsFirstRuleInMediaRule(): void
    {
        $this->subject->append(['p'], 'color: green;', '@media screen');

        $result = $this->subject->getCss();

        self::assertSame('@media screen{p{color: green;}}', $result);
    }

    /**
     * @return string[][]
     *
     * @psalm-return array<string, array<int, array<int, string>>>
     */
    public function equivalentSelectorsDataProvider(): array
    {
        return [
            'one selector' => [['p'], ['p']],
            'two selectors' => [
                ['p', 'ul'],
                ['p', 'ul'],
            ],
            'two selectors in different order' => [
                ['p', 'ul'],
                ['ul', 'p'],
            ],
        ];
    }

    /**
     * @test
     *
     * @param string[] $selectors1
     * @param string[] $selectors2
     *
     * @dataProvider equivalentSelectorsDataProvider
     */
    public function appendCombinesRulesWithEquivalentSelectors(array $selectors1, array $selectors2): void
    {
        $this->subject->append($selectors1, 'color: green;');
        $this->subject->append($selectors2, 'font-size: 16px;');

        $result = $this->subject->getCss();

        $expectedResult = \implode(',', $selectors1) . '{color: green;font-size: 16px;}';

        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function appendInsertsSemicolonCombiningRulesWithoutTrailingSemicolon(): void
    {
        $this->subject->append(['p'], 'color: green');
        $this->subject->append(['p'], 'font-size: 16px');

        $result = $this->subject->getCss();

        self::assertSame('p{color: green;font-size: 16px}', $result);
    }

    /**
     * @return string[][]
     *
     * @psalm-return array<string, array<int, array<int, string>>>
     */
    public function differentSelectorsDataProvider(): array
    {
        return [
            'single selectors' => [
                ['p'],
                ['ul'],
                ['p', 'ul'],
            ],
            'single selector and an entirely different pair' => [
                ['p'],
                ['ul', 'ol'],
                ['p', 'ul', 'ol'],
            ],
            'single selector and a superset pair' => [
                ['p'],
                ['p', 'ul'],
                ['p', 'ul'],
            ],
            'pair of selectors and an entirely different single' => [
                ['p', 'ul'],
                ['ol'],
                ['p', 'ul', 'ol'],
            ],
            'pair of selectors and a subset single' => [
                ['p', 'ul'],
                ['ul'],
                ['p', 'ul'],
            ],
            'entirely different pairs of selectors' => [
                ['p', 'ul'],
                ['ol', 'h1'],
                ['p', 'ul', 'ol', 'h1'],
            ],
            'pairs of selectors with one common' => [
                ['p', 'ul'],
                ['ul', 'ol'],
                ['p', 'ul', 'ol'],
            ],
        ];
    }

    /**
     * @test
     *
     * @param string[] $selectors1
     * @param string[] $selectors2
     * @param string[] $combinedSelectors
     *
     * @dataProvider differentSelectorsDataProvider
     */
    public function appendCombinesSameRulesWithDifferentSelectors(
        array $selectors1,
        array $selectors2,
        array $combinedSelectors
    ): void {
        $this->subject->append($selectors1, 'color: green;');
        $this->subject->append($selectors2, 'color: green;');

        $result = $this->subject->getCss();

        $expectedResult = \implode(',', $combinedSelectors) . '{color: green;}';

        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     *
     * @param string[] $selectors1
     * @param string[] $selectors2
     *
     * @dataProvider differentSelectorsDataProvider
     */
    public function appendNotCombinesDifferentRulesWithDifferentSelectors(array $selectors1, array $selectors2): void
    {
        $this->subject->append($selectors1, 'color: green;');
        $this->subject->append($selectors2, 'font-size: 16px;');

        $result = $this->subject->getCss();

        $expectedResult = \implode(',', $selectors1) . '{color: green;}'
            . \implode(',', $selectors2) . '{font-size: 16px;}';

        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function appendCombinesRulesForSameMediaQueryInMediaRule(): void
    {
        $this->subject->append(['p'], 'color: green;', '@media screen');
        $this->subject->append(['ul'], 'font-size: 16px;', '@media screen');

        $result = $this->subject->getCss();

        self::assertSame('@media screen{p{color: green;}ul{font-size: 16px;}}', $result);
    }

    /**
     * @test
     *
     * @param string[] $selectors1
     * @param string[] $selectors2
     *
     * @dataProvider equivalentSelectorsDataProvider
     */
    public function appendCombinesRulesWithEquivalentSelectorsWithinMediaRule(
        array $selectors1,
        array $selectors2
    ): void {
        $this->subject->append($selectors1, 'color: green;', '@media screen');
        $this->subject->append($selectors2, 'font-size: 16px;', '@media screen');

        $result = $this->subject->getCss();

        $expectedResult = '@media screen{' . \implode(',', $selectors1) . '{color: green;font-size: 16px;}}';

        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     *
     * @param string[] $selectors1
     * @param string[] $selectors2
     * @param string[] $combinedSelectors
     *
     * @dataProvider differentSelectorsDataProvider
     */
    public function appendCombinesSameRulesWithDifferentSelectorsWithinMediaRule(
        array $selectors1,
        array $selectors2,
        array $combinedSelectors
    ): void {
        $this->subject->append($selectors1, 'color: green;', '@media screen');
        $this->subject->append($selectors2, 'color: green;', '@media screen');

        $result = $this->subject->getCss();

        $expectedResult = '@media screen{' . \implode(',', $combinedSelectors) . '{color: green;}}';

        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function appendNotCombinesRulesForDifferentMediaQueryInMediaRule(): void
    {
        $this->subject->append(['p'], 'color: green;', '@media screen');
        $this->subject->append(['p'], 'color: green;', '@media print');

        $result = $this->subject->getCss();

        self::assertSame('@media screen{p{color: green;}}@media print{p{color: green;}}', $result);
    }

    /**
     * @return mixed[][]
     *
     * @psalm-return array<string, array{0:array<int, string>, 1:string, 2:array<int, string>, 3:string, 4:string}>
     */
    public function combinableRulesDataProvider(): array
    {
        return [
            'same selectors' => [['p'], 'color: green;', ['p'], 'font-size: 16px;', ''],
            'same declarations block' => [['p'], 'color: green;', ['ul'], 'color: green;', ''],
            'same media query' => [['p'], 'color: green;', ['ul'], 'font-size: 16px;', '@media screen'],
        ];
    }

    /**
     * @test
     *
     * @param string[] $rule1Selectors
     * @param string $rule1DeclarationsBlock
     * @param string[] $rule2Selectors
     * @param string $rule2DeclarationsBlock
     * @param string $media
     *
     * @dataProvider combinableRulesDataProvider
     */
    public function appendNotCombinesNonadjacentRules(
        array $rule1Selectors,
        string $rule1DeclarationsBlock,
        array $rule2Selectors,
        string $rule2DeclarationsBlock,
        string $media
    ): void {
        $this->subject->append($rule1Selectors, $rule1DeclarationsBlock, $media);
        $this->subject->append(['.intervening'], '-intervening-property: 0;');
        $this->subject->append($rule2Selectors, $rule2DeclarationsBlock, $media);

        $result = $this->subject->getCss();

        $expectedRule1Css = \implode(',', $rule1Selectors) . '{' . $rule1DeclarationsBlock . '}';
        $expectedRule2Css = \implode(',', $rule2Selectors) . '{' . $rule2DeclarationsBlock . '}';
        if ($media !== '') {
            $expectedRule1Css = $media . '{' . $expectedRule1Css . '}';
            $expectedRule2Css = $media . '{' . $expectedRule2Css . '}';
        }
        $expectedResult = $expectedRule1Css . '.intervening{-intervening-property: 0;}' . $expectedRule2Css;

        self::assertSame($expectedResult, $result);
    }
}
