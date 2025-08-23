<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Constraint;

use Pelago\Emogrifier\Utilities\Preg;

/**
 * This constraint asserts that the string it is evaluated for contains some specific CSS a specific number of times,
 * allowing for cosmetic whitespace differences.
 *
 * The CSS string and expected number of occurrences are passed in the constructor.
 */
final class StringContainsCssCount extends CssConstraint
{
    /**
     * @var int<0, max>
     */
    private $count;

    /**
     * @var string
     */
    private $css;

    /**
     * @var non-empty-string
     */
    private $cssPattern;

    /**
     * @param int<0, max> $count
     */
    public function __construct(int $count, string $css)
    {
        parent::__construct();

        $this->count = $count;
        $this->css = $css;
        $this->cssPattern = '/' . self::getCssRegularExpressionMatcher($css) . '/';
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return 'contains exactly ' . $this->count . ' occurrence(s) of CSS `' . $this->css . '`';
    }

    /**
     * Evaluates the constraint for the parameter `$other`.
     *
     * @param mixed $other value or object to evaluate
     */
    protected function matches($other): bool
    {
        if (!\is_string($other)) {
            return false;
        }

        return (new Preg())->matchAll($this->cssPattern, $other) === $this->count;
    }
}
