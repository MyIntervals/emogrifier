<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Css;

use Pelago\Emogrifier\Css\StyleRule;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Css\StyleRule;
 */
final class StyleRuleTest extends TestCase
{
    /**
     * @test
     */
    public function getMediaQueryReturnsMediaQueryProvidedByConstructor(): void
    {
        $mediaQuery = '@media screen and (max-width: 480px)';
        $rule = new \Pelago\Emogrifier\Css\StyleRule($mediaQuery, '*', 'color: black;');

        self::assertSame($mediaQuery, $rule->getMediaQuery());
    }

    /**
     * @test
     */
    public function getMediaQueryTrimsMediaQuery(): void
    {
        $mediaQuery = ' @media screen and (max-width: 480px) ';
        $rule = new \Pelago\Emogrifier\Css\StyleRule($mediaQuery, '*', 'color: black;');

        self::assertSame(\trim($mediaQuery), $rule->getMediaQuery());
    }

    /**
     * @test
     */
    public function mediaQueryCanBeEmpty(): void
    {
        $mediaQuery = '';
        $rule = new \Pelago\Emogrifier\Css\StyleRule($mediaQuery, '*', 'color: black;');

        self::assertSame($mediaQuery, $rule->getMediaQuery());
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
    public function hasNonEmptyMediaQueryForEmptyMediaQueryReturnsFalse(string $mediaQuery): void
    {
        $rule = new \Pelago\Emogrifier\Css\StyleRule($mediaQuery, '*', 'color: black;');

        self::assertFalse($rule->hasNonEmptyMediaQuery());
    }

    /**
     * @test
     */
    public function hasNonEmptyMediaQueryForNonEmptyMediaQueryReturnsTrue(): void
    {
        $rule = new StyleRule('@media all', '*', 'color: black;');

        self::assertTrue($rule->hasNonEmptyMediaQuery());
    }

    /**
     * @test
     *
     * @dataProvider provideEmptyOrWhiteSpaceOnlyStrings
     */
    public function selectorsCannotBeEmpty(string $selectors): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide non-empty selectors.');
        $this->expectExceptionCode(1623263716);

        new \Pelago\Emogrifier\Css\StyleRule('@media screen', $selectors, 'color: black;');
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public function provideSelectors(): array
    {
        return [
            'single selector' => ['h1', ['h1']],
            'single selector with leading whitespace' => [' h1', ['h1']],
            'single selector with trailing whitespace' => ['h1 ', ['h1']],
            'two selectors without whitespace' => ['h1,p', ['h1', 'p']],
            'two selectors with whitespace after the comma' => ['h1, p', ['h1', 'p']],
            'two selectors with whitespace before the comma' => ['h1 ,p', ['h1', 'p']],
        ];
    }

    /**
     * @test
     *
     * @param string $concatenatedSelectors
     * @param array<int, string> $singleSelectors
     *
     * @dataProvider provideSelectors
     */
    public function getSelectorsReturnsSeparateSelectors(string $concatenatedSelectors, array $singleSelectors): void
    {
        $rule = new \Pelago\Emogrifier\Css\StyleRule('', $concatenatedSelectors, 'color: black;');

        self::assertSame($singleSelectors, $rule->getSelectors());
    }

    /**
     * @test
     */
    public function getDeclarationsBlockReturnsDeclarationsBlockProvidedByConstructor(): void
    {
        $declarations = 'color: red; height: 4px;';
        $rule = new \Pelago\Emogrifier\Css\StyleRule('', '*', $declarations);

        self::assertSame($declarations, $rule->getDeclarationsBlock());
    }

    /**
     * @test
     */
    public function getDeclarationsBlockTrimsDeclarationsBlock(): void
    {
        $declarations = ' color: red; height: 4px; ';
        $rule = new \Pelago\Emogrifier\Css\StyleRule('', '*', $declarations);

        self::assertSame(\trim($declarations), $rule->getDeclarationsBlock());
    }

    /**
     * @test
     */
    public function declarationsCanBeEmpty(): void
    {
        $declarations = '';
        $rule = new StyleRule('@media screen', '*', $declarations);

        self::assertSame($declarations, $rule->getDeclarationsBlock());
    }

    /**
     * @test
     *
     * @dataProvider provideEmptyOrWhiteSpaceOnlyStrings
     */
    public function hasDeclarationsBlockForEmptyDeclarationsBlockReturnsFalse(string $declarations): void
    {
        $styleRule = new \Pelago\Emogrifier\Css\StyleRule('', '*', $declarations);

        self::assertFalse($styleRule->hasNonEmptyDeclarationsBlock());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function provideNonEmptyDeclarationsBlock(): array
    {
        return [
            'non-empty' => ['color: black;'],
            'non-empty with trailing whitespace' => ['color: black; '],
            'non-empty with leading whitespace' => [' color: black;'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideNonEmptyDeclarationsBlock
     */
    public function hasDeclarationsBlockForNonEmptyDeclarationsBlockReturnsTrue(string $declarations): void
    {
        $styleRule = new \Pelago\Emogrifier\Css\StyleRule('', '*', $declarations);

        self::assertTrue($styleRule->hasNonEmptyDeclarationsBlock());
    }
}
