<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Utilities;

/**
 * PHP's `preg_*` functions can return `false` on failure.
 * Failure is rare but may occur with a complex pattern applied to a long subject.
 * Catastrophic backtracking may occur ({@see https://www.regular-expressions.info/catastrophic.html}).
 * Failure may also occur due to programmer error, if an invalid pattern is provided.
 *
 * Catering for failure in each case clutters up the code with error handling.
 * This class provides wrappers for some `preg_*` functions, with errors handled either
 * - by throwing an exception, or
 * - by triggering a user error and providing fallback logic (e.g. returning the subject string unmodified).
 *
 * @internal
 */
final class Preg
{
    /**
     * whether to throw exceptions on errors (or call `trigger_error` and implement fallback)
     *
     * @var bool
     */
    private $throwExceptions = false;

    /**
     * Sets whether exceptions should be thrown if an error occurs.
     */
    public function throwExceptions(bool $throw): self
    {
        $this->throwExceptions = $throw;

        return $this;
    }

    /**
     * Wraps `preg_replace`, though does not support `$subject` being an array.
     * If an error occurs, and exceptions are not being thrown, the original `$subject` is returned.
     *
     * @param non-empty-string|non-empty-array<non-empty-string> $pattern
     * @param string|non-empty-array<string> $replacement
     *
     * @throws \RuntimeException
     */
    public function replace($pattern, $replacement, string $subject, int $limit = -1, ?int &$count = null): string
    {
        $result = \preg_replace($pattern, $replacement, $subject, $limit, $count);

        if ($result === null) {
            $this->logOrThrowPregLastError();
            $result = $subject;
        }

        return $result;
    }

    /**
     * Wraps `preg_replace_callback`, though does not support `$subject` being an array.
     * If an error occurs, and exceptions are not being thrown, the original `$subject` is returned.
     *
     * Note that (unlike when calling `preg_replace_callback`), `$callback` cannot be a non-public method
     * represented by an array comprising an object or class name and the method name.
     * To circumvent that, use `\Closure::fromCallable([$objectOrClassName, 'method'])`.
     *
     * @param non-empty-string|non-empty-array<non-empty-string> $pattern
     *
     * @throws \RuntimeException
     */
    public function replaceCallback(
        $pattern,
        callable $callback,
        string $subject,
        int $limit = -1,
        ?int &$count = null
    ): string {
        $result = \preg_replace_callback($pattern, $callback, $subject, $limit, $count);

        if ($result === null) {
            $this->logOrThrowPregLastError();
            $result = $subject;
        }

        return $result;
    }

    /**
     * Wraps `preg_split`.
     * If an error occurs, and exceptions are not being thrown,
     * a single-element array containing the original `$subject` is returned.
     * This method does not support the `PREG_SPLIT_OFFSET_CAPTURE` flag and will throw an excpetion if it is specified.
     *
     * @param non-empty-string $pattern
     *
     * @return array<int, string>
     *
     * @throws \RuntimeException
     */
    public function split(string $pattern, string $subject, int $limit = -1, int $flags = 0): array
    {
        if (($flags & PREG_SPLIT_OFFSET_CAPTURE) !== 0) {
            throw new \RuntimeException('PREG_SPLIT_OFFSET_CAPTURE is not supported by Preg::split');
        }

        $result = \preg_split($pattern, $subject, $limit, $flags);

        if ($result === false) {
            $this->logOrThrowPregLastError();
            $result = [$subject];
        }

        return $result;
    }


    /**
     * Obtains the name of the error constant for `preg_last_error`
     * (based on code posted at {@see https://www.php.net/manual/en/function.preg-last-error.php#124124})
     * and puts it into an error message which is either passed to `trigger_error`
     * or used in the exception which is thrown (depending on the `$throwExceptions` property).
     *
     * @throws \RuntimeException
     */
    private function logOrThrowPregLastError(): void
    {
        $pcreConstants = \get_defined_constants(true)['pcre'];
        $pcreErrorConstantNames = \array_flip(\array_filter(
            $pcreConstants,
            static function (string $key): bool {
                return \substr($key, -6) === '_ERROR';
            },
            ARRAY_FILTER_USE_KEY
        ));

        $pregLastError = \preg_last_error();
        $message = 'PCRE regex execution error `' . (string) ($pcreErrorConstantNames[$pregLastError] ?? $pregLastError)
            . '`';

        if ($this->throwExceptions) {
            throw new \RuntimeException($message, 1592870147);
        }
        \trigger_error($message);
    }
}
