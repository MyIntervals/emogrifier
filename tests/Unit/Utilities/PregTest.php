<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Utilities;

use Pelago\Emogrifier\Utilities\Preg;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Utilities\Preg
 */
final class PregTest extends TestCase
{
    /**
     * @test
     */
    public function throwExceptionsProvidesFluentInterface(): void
    {
        $subject = new Preg();

        $result = $subject->throwExceptions(false);

        self::assertSame($subject, $result);
    }

    /**
     * @test
     */
    public function exceptionNotThrownByDefault(): void
    {
        $this->expectNotToPerformAssertions();
        $subject = new Preg();

        // Version 6 of PHPUnit allowed setting `PHPUnit\Framework\Error\Notice::$enabled` to `false`
        // to prevent its error handler throwing exceptions upon `trigger_error`.
        // This functionality has been removed by version 9.
        // Version 10 allows use of the #[WithoutErrorHandler] attribute, but this requires PHP 8.0 anyway.
        // So we must `@` to suppress the triggered error.
        // This also means we can't test for our own triggered error, since `preg_*` also triggers an error.
        @$subject->replace('/', '', '');
    }

    /**
     * @test
     */
    public function exceptionThrownAfterThrowExceptionsTurnedOn(): void
    {
        $this->expectException(\RuntimeException::class);
        $subject = new Preg();

        $subject->throwExceptions(true);
        @$subject->replace('/', '', '');
    }

    /**
     * @test
     */
    public function exceptionNotThrownAfterThrowExceptionsTurnedOff(): void
    {
        $this->expectNotToPerformAssertions();
        $subject = new Preg();

        $subject->throwExceptions(true);
        $subject->throwExceptions(false);
        @$subject->replace('/', '', '');
    }

    /**
     * @return array<array<array-key, string|non-empty-array<string>|int>>
     */
    public function providePregReplaceArgumentsAndExpectedResult(): array
    {
        return [
            'string arguments' => [
                'pattern' => '/a/',
                'replacement' => 'fab',
                'subject' => 'abba',
                'limit' => -1,
                'expect' => 'fabbbfab',
            ],
            'array pattern' => [
                'pattern' => ['/a/', '/b/'],
                'replacement' => 'fab',
                'subject' => 'abba',
                'limit' => -1,
                'expect' => 'fafabfabfabfafab',
            ],
            'array pattern and replacement' => [
                'pattern' => ['/a/', '/b/'],
                'replacement' => ['fab', 'z'],
                'subject' => 'abba',
                'limit' => -1,
                'expect' => 'fazzzfaz',
            ],
            'with limit' => [
                'pattern' => '/a/',
                'replacement' => 'fab',
                'subject' => 'abba',
                'limit' => 1,
                'expect' => 'fabbba',
            ],
        ];
    }

    /**
     * @test
     *
     * @param non-empty-string|non-empty-array<non-empty-string> $pattern
     * @param string|non-empty-array<string> $replacement
     *
     * @dataProvider providePregReplaceArgumentsAndExpectedResult
     */
    public function replaceReplaces($pattern, $replacement, string $subject, int $limit, string $expectedResult): void
    {
        $testSubject = new Preg();

        $result = $testSubject->replace($pattern, $replacement, $subject, $limit);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     *
     * @param non-empty-string|non-empty-array<non-empty-string> $pattern
     * @param string|non-empty-array<string> $replacement
     *
     * @dataProvider providePregReplaceArgumentsAndExpectedResult
     */
    public function replaceCallbackReplaces(
        $pattern,
        $replacement,
        string $subject,
        int $limit,
        string $expectedResult
    ): void {
        $callback = static function (array $matches) use ($replacement): string {
            if (\is_array($replacement)) {
                static $lastMatch;
                static $replacementIndex = -1;
                if ($matches[0] !== $lastMatch) {
                    ++$replacementIndex;
                    $lastMatch = $matches[0];
                }
                return $replacement[$replacementIndex];
            } else {
                return $replacement;
            }
        };
        $testSubject = new Preg();

        $result = $testSubject->replaceCallback($pattern, $callback, $subject, $limit);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function replaceSetsCount(): void
    {
        $subject = new Preg();

        $subject->replace('/a/', 'fab', 'abba', -1, $count);

        self::assertSame(2, $count);
    }

    /**
     * @test
     */
    public function replaceCallbackSetsCount(): void
    {
        $subject = new Preg();

        $subject->replaceCallback(
            '/a/',
            static function (array $matches): string {
                return 'fab';
            },
            'abba',
            -1,
            $count
        );

        self::assertSame(2, $count);
    }

    /**
     * @test
     */
    public function replaceReturnsSubjectOnError(): void
    {
        $subject = new Preg();

        $result = @$subject->replace('/', 'fab', 'abba');

        self::assertSame('abba', $result);
    }

    /**
     * @test
     */
    public function replaceCallbackReturnsSubjectOnError(): void
    {
        $subject = new Preg();

        $result = @$subject->replaceCallback(
            '/',
            static function (array $matches): string {
                return 'fab';
            },
            'abba'
        );

        self::assertSame('abba', $result);
    }
}
