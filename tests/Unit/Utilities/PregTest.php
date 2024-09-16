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
     * @var string|array<string>
     */
    private $replaceCallbackReplacement = '';

    /**
     * @var ?string
     */
    private $lastReplaceCallbackMatch;

    /**
     * @var int
     */
    private $replaceCallbackReplacementIndex = 0;

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
     * @return array<non-empty-string, array{0: callable}>
     */
    public function provideCallableToCallMethodErroneously(): array
    {
        return [
            'replace' => [
                static function (Preg $testSubject): void {
                    $testSubject->replace('/', '', '');
                },
            ],
            'replaceCallback' => [
                static function (Preg $testSubject): void {
                    $testSubject->replaceCallback(
                        '/',
                        static function (string $matches): string {
                            return '';
                        },
                        ''
                    );
                },
            ],
            'split' => [
                static function (Preg $testSubject): void {
                    $testSubject->split('/', '');
                },
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideCallableToCallMethodErroneously
     */
    public function exceptionIsNotThrownByDefault(callable $methodCaller): void
    {
        $this->expectNotToPerformAssertions();
        $subject = new Preg();

        // Version 6 of PHPUnit allowed setting `PHPUnit\Framework\Error\Notice::$enabled` to `false`
        // to prevent its error handler throwing exceptions upon `trigger_error`.
        // This functionality has been removed by version 9.
        // Version 10 allows use of the #[WithoutErrorHandler] attribute, but this requires PHP 8.0 anyway.
        // So we must `@` to suppress the triggered error.
        // This also means we can't test for our own triggered error, since `preg_*` also triggers an error.
        @$methodCaller($subject);
    }

    /**
     * @test
     *
     * @dataProvider provideCallableToCallMethodErroneously
     */
    public function exceptionIsThrownAfterThrowExceptionsTurnedOn(callable $methodCaller): void
    {
        $this->expectException(\RuntimeException::class);
        $subject = new Preg();

        $subject->throwExceptions(true);
        @$methodCaller($subject);
    }

    /**
     * @test
     *
     * @dataProvider provideCallableToCallMethodErroneously
     */
    public function exceptionIsNotThrownAfterThrowExceptionsTurnedOff(callable $methodCaller): void
    {
        $this->expectNotToPerformAssertions();
        $subject = new Preg();

        $subject->throwExceptions(true);
        $subject->throwExceptions(false);
        @$methodCaller($subject);
    }

    /**
     * @return array<non-empty-string, array{
     *             pattern: non-empty-string|array<non-empty-string>,
     *             replacement: string|array<string>,
     *             subject: string,
     *             limit: int,
     *             expect: string,
     *         }>
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
        $this->replaceCallbackReplacement = $replacement;
        $this->lastReplaceCallbackMatch = null;
        $this->replaceCallbackReplacementIndex = -1;
        $testSubject = new Preg();

        $result = $testSubject->replaceCallback(
            $pattern,
            \Closure::fromCallable([$this, 'callbackForReplaceCallback']),
            $subject,
            $limit
        );

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
     * @return array<non-empty-string, array{
     *             pattern: non-empty-string,
     *             subject: string,
     *             limit: int,
     *             flags: int,
     *             expect: array<int, string>|array<int, array{0: string, 1: int}>,
     *         }>
     */
    public function providePregSplitArgumentsAndExpectedResult(): array
    {
        return [
            'simple arguments' => [
                'pattern' => '/a/',
                'subject' => 'abba',
                'limit' => -1,
                'flags' => 0,
                'expect' => ['', 'bb', ''],
            ],
            'with limit' => [
                'pattern' => '/a/',
                'subject' => 'abba',
                'limit' => 2,
                'flags' => 0,
                'expect' => ['', 'bba'],
            ],
            // It is only necessary to test that the `$flags` parameter is passed on.
            'with PREG_SPLIT_NO_EMPTY' => [
                'pattern' => '/a/',
                'subject' => 'abba',
                'limit' => -1,
                'flags' => PREG_SPLIT_NO_EMPTY,
                'expect' => ['bb'],
            ],
        ];
    }

    /**
     * @test
     *
     * @param non-empty-string $pattern
     * @param array<int, string>|array<int, array{0: string, 1: int}> $expectedResult
     *
     * @dataProvider providePregSplitArgumentsAndExpectedResult
     */
    public function splitSplits(string $pattern, string $subject, int $limit, int $flags, array $expectedResult): void
    {
        $testSubject = new Preg();

        $result = $testSubject->split($pattern, $subject, $limit, $flags);

        self::assertSame($expectedResult, $result);
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

    /**
     * @test
     */
    public function splitReturnsArrayContainingSubjectOnError(): void
    {
        $subject = new Preg();

        $result = @$subject->split('/', 'abba');

        self::assertSame(['abba'], $result);
    }

    /**
     * @test
     */
    public function splitWithOffsetCaptureIsNotSupported(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1726506348);
        $this->expectExceptionMessage('PREG_SPLIT_OFFSET_CAPTURE');
        $subject = new Preg();

        $result = @$subject->split('/', 'abba', -1, PREG_SPLIT_OFFSET_CAPTURE);
    }

    /**
     * @param array<int, string> $matches
     */
    private function callbackForReplaceCallback(array $matches): string
    {
        if (\is_array($this->replaceCallbackReplacement)) {
            if ($matches[0] !== $this->lastReplaceCallbackMatch) {
                ++$this->replaceCallbackReplacementIndex;
                $this->lastReplaceCallbackMatch = $matches[0];
            }
            return $this->replaceCallbackReplacement[$this->replaceCallbackReplacementIndex];
        } else {
            return $this->replaceCallbackReplacement;
        }
    }
}
