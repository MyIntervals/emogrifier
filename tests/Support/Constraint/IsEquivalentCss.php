<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Constraint;

/**
 * This constraint asserts that the string it is evaluated for is equivalent to some specific CSS, allowing for cosmetic
 * whitespace differences.
 *
 * The CSS string passed in the constructor.
 */
final class IsEquivalentCss extends CssConstraint
{
    /**
     * @var string
     */
    private $css;

    /**
     * @var string
     */
    private $cssPattern;

    /**
     * @param string $css
     */
    public function __construct(string $css)
    {
        parent::__construct();

        $this->css = $css;
        $this->cssPattern = '/^\\s*+' . self::getCssRegularExpressionMatcher(\trim($css)) . '\\s*+$/';
    }

    /**
     * @return string a string representation of the constraint
     */
    public function toString(): string
    {
        return 'equals CSS `' . $this->css . '`';
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

        return \preg_match($this->cssPattern, $other) > 0;
    }
}
