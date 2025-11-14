<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Utilities;

use Pelago\Emogrifier\Utilities\DeclarationBlockParser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Utilities\DeclarationBlockParser
 */
final class DeclarationBlockParserTest extends TestCase
{
    /**
     * @return array<non-empty-string, array{name: non-empty-string, expect: non-empty-string}>
     */
    public function providePropertyNameAndExpectedNormalization(): array
    {
        return [
            'standard property' => [
                'name' => 'color',
                'expect' => 'color',
            ],
            'vendor property' => [
                'name' => '-moz-box-sizing',
                'expect' => '-moz-box-sizing',
            ],
            'custom property' => [
                'name' => '--text-color',
                'expect' => '--text-color',
            ],
            'custom property with numbers' => [
                'name' => '--base-size-16',
                'expect' => '--base-size-16',
            ],
            'custom property with everything' => [
                'name' => '--Base_size-4u',
                'expect' => '--Base_size-4u',
            ],
            'standard property with some uppercase' => [
                'name' => 'cOlOr',
                'expect' => 'color',
            ],
            'vendor property with some uppercase' => [
                'name' => '-MOZ-box-Sizing',
                'expect' => '-moz-box-sizing',
            ],
            'custom property with some uppercase' => [
                'name' => '--TEXT-Color',
                'expect' => '--TEXT-Color',
            ],
        ];
    }

    /**
     * @test
     *
     * @param non-empty-string $name
     * @param non-empty-string $expectedNormalization
     *
     * @dataProvider providePropertyNameAndExpectedNormalization
     */
    public function normalizesPropertyName(string $name, string $expectedNormalization): void
    {
        $subject = new DeclarationBlockParser();

        $result = $subject->normalizePropertyName($name);

        self::assertSame($expectedNormalization, $result);
    }

    /**
     * @return array<non-empty-string, array{string: string, array: array<non-empty-string, non-empty-string>}>
     */
    public function provideDeclratationBlockAsStringAndArray(): array
    {
        return [
            'empty' => [
                'string' => '',
                'array' => [],
            ],
            'whitespace only' => [
                'string' => " \r\n\t",
                'array' => [],
            ],
            'semicolon only' => [
                'string' => ';',
                'array' => [],
            ],
            'whitespace and semicolon only' => [
                'string' => " \r\n\t; \r\n\t",
                'array' => [],
            ],
            '1 declaration without trailing semicolon' => [
                'string' => 'color: green',
                'array' => ['color' => 'green'],
            ],
            '1 declaration with trailing semicolon' => [
                'string' => 'color: green;',
                'array' => ['color' => 'green'],
            ],
            '1 declaration with leading semicolon' => [
                'string' => '; color: green',
                'array' => ['color' => 'green'],
            ],
            'declaration with space before colon' => [
                'string' => 'color : green;',
                'array' => ['color' => 'green'],
            ],
            'declaration without space after colon' => [
                'string' => 'color:green;',
                'array' => ['color' => 'green'],
            ],
            'declaration without value' => [
                'string' => 'color: ;',
                'array' => [],
            ],
            'declaration with only value' => [
                'string' => 'red;',
                'array' => [],
            ],
            'declaration without property name' => [
                'string' => ' : red;',
                'array' => [],
            ],
            '2 declarations' => [
                'string' => 'color: green; background-color: green;',
                'array' => ['color' => 'green', 'background-color' => 'green'],
            ],
            '2 declarations with extra semicolon between' => [
                'string' => 'color: green;; background-color: green;',
                'array' => ['color' => 'green', 'background-color' => 'green'],
            ],
            '2 declarations with newline between' => [
                'string' => 'color: green;' . "\n" . 'background-color: green;',
                'array' => ['color' => 'green', 'background-color' => 'green'],
            ],
            'declaration with !important' => [
                'string' => 'color: green !important;',
                'array' => ['color' => 'green !important'],
            ],
            'vendor property declaration' => [
                'string' => '-moz-box-sizing: border-box;',
                'array' => ['-moz-box-sizing' => 'border-box'],
            ],
            'custom property definition' => [
                'string' => '--text-color: green;',
                'array' => ['--text-color' => 'green'],
            ],
            'custom property with numbers definition' => [
                'string' => '--base-size-16: 1rem;',
                'array' => ['--base-size-16' => '1rem'],
            ],
            'custom property with everything definition' => [
                'string' => '--Base_size-4u: normal;',
                'array' => ['--Base_size-4u' => 'normal'],
            ],
            'specification test single character allowed' => [
                'string' => 'x: 0;',
                'array' => ['x' => '0'],
            ],
            'specification test hyphen single character allowed' => [
                'string' => '-o: normal;',
                'array' => ['-o' => 'normal'],
            ],
            'specification test underscore allowed' => [
                'string' => '_allowed: normal;',
                'array' => ['_allowed' => 'normal'],
            ],
            'specification test hyphen underscore allowed' => [
                'string' => '-_allowed: normal;',
                'array' => ['-_allowed' => 'normal'],
            ],
            'specification test double hyphen underscore allowed' => [
                'string' => '--_allowed: normal;',
                'array' => ['--_allowed' => 'normal'],
            ],
            'specification test double underscore allowed' => [
                'string' => '__allowed: normal;',
                'array' => ['__allowed' => 'normal'],
            ],
            'specification test number not allowed' => [
                'string' => '2not-allowed: unset;',
                'array' => [],
            ],
            'specification test hyphen number not allowed' => [
                'string' => '-2not-allowed: unset;',
                'array' => [],
            ],
            'specification test double hyphen number allowed' => [
                'string' => '--2allowed: normal;',
                'array' => ['--2allowed' => 'normal'],
            ],
            'specification test backslash not allowed' => [
                'string' => 'not\\allowed: unset;',
                'array' => [],
            ],
            'declaration using custom property' => [
                'string' => 'color: var(--text-color);',
                'array' => ['color' => 'var(--text-color)'],
            ],
            'declaration with uppercase in property name' => [
                'string' => 'CoLoR: green;',
                'array' => ['color' => 'green'],
            ],
            'vendor property declaration with uppercase in property name' => [
                'string' => '-Moz-Box-Sizing: border-box;',
                'array' => ['-moz-box-sizing' => 'border-box'],
            ],
            'custom property definition with uppercase in property name' => [
                'string' => '--Text-Color: green;',
                'array' => ['--Text-Color' => 'green'],
            ],
            'declaration using custom property with uppercase in its name' => [
                'string' => 'color: var(--Text-Color);',
                'array' => ['color' => 'var(--Text-Color)'],
            ],
            'declaration with uppercase in property value' => [
                'string' => 'font-family: Courier;',
                'array' => ['font-family' => 'Courier'],
            ],
            'declaration using CSS function' => [
                'string' => 'width: calc(50% + 10vw + 10rem);',
                'array' => ['width' => 'calc(50% + 10vw + 10rem)'],
            ],
            'shorthand property declaration' => [
                'string' => 'border: 2px solid green',
                'array' => ['border' => '2px solid green'],
            ],
            'declaration with text value (single quotes)' => [
                'string' => 'content: \'Hello universe\';',
                'array' => ['content' => '\'Hello universe\''],
            ],
            'declaration with text value (double quotes)' => [
                'string' => 'content: "Hello universe";',
                'array' => ['content' => '"Hello universe"'],
            ],
            'font declaration with size, line-height and family' => [
                'string' => 'font: 1.2em/2 "Fira Sans", sans-serif;',
                'array' => ['font' => '1.2em/2 "Fira Sans", sans-serif'],
            ],
            'declaration using data URL with charset' => [
                'string' => 'text: url("data:text/plain;charset=UTF-8,Hello%20universe");',
                'array' => ['text' => 'url("data:text/plain;charset=UTF-8,Hello%20universe")'],
            ],
            'declaration using data URL with base64-encoding' => [
                'string' => 'text: url("data:;base64,SGVsbG8gdW5pdmVyc2U=");',
                'array' => ['text' => 'url("data:;base64,SGVsbG8gdW5pdmVyc2U=")'],
            ],
            'declaration using data URL with charset and base64-encoding' => [
                'string' => 'text: url("data:text/plain;charset=UTF-8;base64,SGVsbG8gdW5pdmVyc2U=");',
                'array' => ['text' => 'url("data:text/plain;charset=UTF-8;base64,SGVsbG8gdW5pdmVyc2U=")'],
            ],
        ];
    }

    /**
     * @test
     *
     * @param array<non-empty-string, non-empty-string> $declratationBlockAsArray
     *
     * @dataProvider provideDeclratationBlockAsStringAndArray
     */
    public function parses(string $declratationBlockAsString, array $declratationBlockAsArray): void
    {
        $subject = new DeclarationBlockParser();

        $result = $subject->parse($declratationBlockAsString);

        self::assertSame($declratationBlockAsArray, $result);
    }

    /**
     * @test
     */
    public function overridesEarlierDeclarationWithLaterOne(): void
    {
        $subject = new DeclarationBlockParser();

        $result = $subject->parse('color: red; color: green;');

        self::assertSame(['color' => 'green'], $result);
    }

    /**
     * From a black (or orange) box point of view, the class under test may or may not implement some caching.
     * If it does, check that the same result is obtained from a second identical request.
     *
     * @test
     */
    public function providesConsistentResults(): void
    {
        $subject = new DeclarationBlockParser();
        $declarationBlock = 'color: green;';

        $firstResult = $subject->parse($declarationBlock);
        $secondResult = $subject->parse($declarationBlock);

        self::assertSame($firstResult, $secondResult);
    }
}
