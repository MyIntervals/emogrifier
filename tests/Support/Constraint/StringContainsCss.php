<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Constraint;

use Pelago\Emogrifier\Utilities\Preg;

/**
 * This constraint asserts that the string it is evaluated for contains some specific CSS, allowing for cosmetic
 * whitespace differences.
 *
 * The CSS string passed in the constructor.
 */
final class StringContainsCss extends CssConstraint
{
    /**
     * @var string
     */
    private $css;

    /**
     * @var non-empty-string
     */
    private $cssPattern;

    /**
     * @param string $css
     */
    public function __construct(string $css)
    {
        parent::__construct();

        $this->css = $css;
        $this->cssPattern = '/' . self::getCssRegularExpressionMatcher($css) . '/';
    }

    /**
     * @return string a string representation of the constraint
     */
    public function toString(): string
    {
        return 'contains CSS `' . $this->css . '`';
    }

    /**
     * Evaluates the constraint for the parameter `$other`.
     *
     * @param mixed $other value or object to evaluate
     *
     * @return bool `true` if the constraint is met, `false` otherwise
     */
    protected function matches($other): bool
    {
        if (!\is_string($other)) {
            return false;
        }

        return (new Preg())->match($this->cssPattern, $other) !== 0;
    }
}
