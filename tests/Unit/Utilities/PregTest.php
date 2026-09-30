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
     * @var string|null
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
            'match' => [
                static function (Preg $testSubject): void {
                    $testSubject->match('/', '');
                },
            ],
            'matchAll' => [
                static function (Preg $testSubject): void {
                    $testSubject->matchAll('/', '');
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
    public function replaceReturnsSubjectOnError(): void
    {
        $subject = new Preg();

        $result = @$subject->replace('/', 'fab', 'abba');

        self::assertSame('abba', $result);
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

        $result = $subject->split('/a/', 'abba', -1, PREG_SPLIT_OFFSET_CAPTURE);
    }

    /**
     * @return array<non-empty-string, array{
     *             pattern: non-empty-string,
     *             subject: string,
     *             expect: int,
     *         }>
     */
    public function providePregMatchArgumentsAndExpectedMatchCount(): array
    {
        return [
            'no match' => [
                'pattern' => '/fab/',
                'subject' => 'abba',
                'expect' => 0,
            ],
            'with match' => [
                'pattern' => '/a/',
                'subject' => 'abba',
                'expect' => 1,
            ],
        ];
    }

    /**
     * @test
     *
     * @param non-empty-string $pattern
     *
     * @dataProvider providePregMatchArgumentsAndExpectedMatchCount
     */
    public function matchReturnsMatchCount(string $pattern, string $subject, int $expectedMatchCount): void
    {
        $testSubject = new Preg();

        $result = $testSubject->match($pattern, $subject);

        self::assertSame($expectedMatchCount, $result);
    }

    /**
     * @return array<non-empty-string, array{
     *             pattern: non-empty-string,
     *             subject: string,
     *             expect: array<int, string>,
     *         }>
     */
    public function providePregMatchArgumentsAndExpectedMatches(): array
    {
        return [
            'no match' => [
                'pattern' => '/fab/',
                'subject' => 'abba',
                'expect' => [],
            ],
            'with match' => [
                'pattern' => '/a/',
                'subject' => 'abba',
                'expect' => ['a'],
            ],
            'with subpattern match' => [
                'pattern' => '/a(b)/',
                'subject' => 'abba',
                'expect' => ['ab', 'b'],
            ],
        ];
    }

    /**
     * @test
     *
     * @param non-empty-string $pattern
     * @param array<int, string> $expectedMatches
     *
     * @dataProvider providePregMatchArgumentsAndExpectedMatches
     */
    public function matchSetsMatches(string $pattern, string $subject, array $expectedMatches): void
    {
        $testSubject = new Preg();

        $testSubject->match($pattern, $subject, $matches);

        self::assertSame($expectedMatches, $matches);
    }

    /**
     * @test
     */
    public function matchReturnsZeroOnError(): void
    {
        $subject = new Preg();

        $result = @$subject->match('/', 'abba');

        self::assertSame(0, $result);
    }

    /**
     * @test
     */
    public function matchSetsMatchesToEmptyArrayOnError(): void
    {
        $subject = new Preg();

        @$subject->match('/', 'abba', $matches);

        self::assertSame([], $matches);
    }

    /**
     * @return array<non-empty-string, array{
     *             pattern: non-empty-string,
     *             subject: string,
     *             expect: int,
     *         }>
     */
    public function providePregMatchAllArgumentsAndExpectedMatchCount(): array
    {
        return [
            'no match' => [
                'pattern' => '/fab/',
                'subject' => 'abba',
                'expect' => 0,
            ],
            'one match' => [
                'pattern' => '/ab/',
                'subject' => 'abba',
                'expect' => 1,
            ],
            'two matches' => [
                'pattern' => '/a/',
                'subject' => 'abba',
                'expect' => 2,
            ],
        ];
    }

    /**
     * @test
     *
     * @param non-empty-string $pattern
     *
     * @dataProvider providePregMatchAllArgumentsAndExpectedMatchCount
     */
    public function matchAllReturnsMatchCount(string $pattern, string $subject, int $expectedMatchCount): void
    {
        $testSubject = new Preg();

        $result = $testSubject->matchAll($pattern, $subject);

        self::assertSame($expectedMatchCount, $result);
    }

    /**
     * @return array<non-empty-string, array{
     *             pattern: non-empty-string,
     *             subject: string,
     *             expect: array<int, array<int, string>>,
     *         }>
     */
    public function providePregMatchAllArgumentsAndExpectedMatches(): array
    {
        return [
            'no match' => [
                'pattern' => '/fab/',
                'subject' => 'abba',
                'expect' => [[]],
            ],
            'one match' => [
                'pattern' => '/ab/',
                'subject' => 'abba',
                'expect' => [['ab']],
            ],
            'two matches' => [
                'pattern' => '/a/',
                'subject' => 'abba',
                'expect' => [['a', 'a']],
            ],
            'with subpattern match' => [
                'pattern' => '/a(b)/',
                'subject' => 'abba',
                'expect' => [['ab'], ['b']],
            ],
            'with two subpattern matches' => [
                'pattern' => '/a(b|$)/',
                'subject' => 'abba',
                'expect' => [['ab', 'a'], ['b', '']],
            ],
            'with matches for two subpatterns' => [
                'pattern' => '/a(b(b))/',
                'subject' => 'abba',
                'expect' => [['abb'], ['bb'], ['b']],
            ],
        ];
    }

    /**
     * @test
     *
     * @param non-empty-string $pattern
     * @param array<int, array<int, string>> $expectedMatches
     *
     * @dataProvider providePregMatchAllArgumentsAndExpectedMatches
     */
    public function matchAllSetsMatches(string $pattern, string $subject, array $expectedMatches): void
    {
        $testSubject = new Preg();

        $testSubject->matchAll($pattern, $subject, $matches);

        self::assertSame($expectedMatches, $matches);
    }

    /**
     * @test
     */
    public function matchAllReturnsZeroOnError(): void
    {
        $subject = new Preg();

        $result = @$subject->matchAll('/', 'abba');

        self::assertSame(0, $result);
    }

    /**
     * In the real world it will be valid but complex patterns that fail, but that is impossible to reliably simulate.
     *
     * @return array<non-empty-string, array{
     *             pattern: non-empty-string,
     *             subpatternCount: int,
     *         }>
     */
    public function provideFailingPatternAndSubpatternCount(): array
    {
        return [
            'no subpatterns' => [
                'pattern' => '/',
                'subpatternCount' => 0,
            ],
            'one subpattern' => [
                'pattern' => '/(a)',
                'subpatternCount' => 1,
            ],
            'two subpattern' => [
                'pattern' => '/(a)(b)',
                'subpatternCount' => 2,
            ],
        ];
    }

    /**
     * @test
     *
     * @param non-empty-string $pattern
     *
     * @dataProvider provideFailingPatternAndSubpatternCount
     */
    public function matchAllSetsMatchesToSufficientLengthArrayOfEmptyArraysOnError(
        string $pattern,
        int $subpatternCount
    ): void {
        $subject = new Preg();

        @$subject->matchAll($pattern, 'abba', $matches);

        // `assertCountAtLeast` would be more ideal to test the looser documented contract.
        self::assertCount($subpatternCount + 1, $matches);

        $matchesWithoutEmptyArrays = \array_filter(
            $matches,
            static function (array $patternOrSubpatternMatches): bool {
                return $patternOrSubpatternMatches !== [];
            }
        );
        self::assertCount(0, $matchesWithoutEmptyArrays);
    }
}
