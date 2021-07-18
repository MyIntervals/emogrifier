<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Caching;

use Pelago\Emogrifier\Caching\SimpleStringCache;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\Caching\SimpleStringCache
 */
final class SimpleStringCacheTest extends TestCase
{
    /**
     * @var SimpleStringCache
     */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new SimpleStringCache();
    }

    /**
     * @test
     */
    public function hasForEmptyKeyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1625995840);
        $this->expectExceptionMessage('Please provide a non-empty key.');

        $this->subject->has('');
    }

    /**
     * @test
     */
    public function hasInitiallyReturnsFalseForAnyKey(): void
    {
        self::assertFalse($this->subject->has('hello'));
    }

    /**
     * @test
     */
    public function hasForKeyThatHasNotBeenSetReturnsFalse(): void
    {
        $this->subject->set('hello', 'world');

        self::assertFalse($this->subject->has('what'));
    }

    /**
     * @test
     */
    public function hasForKeyThatHasBeenSetReturnsTrue(): void
    {
        $key = 'hello';
        $this->subject->set($key, 'world');

        self::assertTrue($this->subject->has($key));
    }

    /**
     * @test
     */
    public function hasForKeyThatHasBeenSetAndOverwrittenReturnsTrue(): void
    {
        $key = 'hello';
        $this->subject->set($key, 'world');
        $this->subject->set($key, 'PHP');

        self::assertTrue($this->subject->has($key));
    }

    /**
     * @test
     */
    public function getForEmptyKeyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1625995840);
        $this->expectExceptionMessage('Please provide a non-empty key.');

        $this->subject->get('');
    }

    /**
     * @test
     */
    public function setForEmptyKeyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1625995840);
        $this->expectExceptionMessage('Please provide a non-empty key.');

        $this->subject->set('', 'some value');
    }

    /**
     * @test
     */
    public function getForKeyThatHasNotBeenSetThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('You can only call `get` with a key for an existing value.');
        $this->expectExceptionCode(1625996246);

        $this->subject->get('hello');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideValues(): array
    {
        return [
            'empty string' => [''],
            'non-empty string' => ['hello'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideValues
     */
    public function getForKeyThatHasBeenSetReturnsSetValue(string $value): void
    {
        $key = 'hello';

        $this->subject->set($key, $value);

        self::assertSame($value, $this->subject->get($key));
    }

    /**
     * @test
     */
    public function getForKeyThatHasBeenSetTwiceReturnsValueSetLast(): void
    {
        $key = 'hello';
        $value = 'world';

        $this->subject->set($key, 'coffee');
        $this->subject->set($key, $value);

        self::assertSame($value, $this->subject->get($key));
    }
}
