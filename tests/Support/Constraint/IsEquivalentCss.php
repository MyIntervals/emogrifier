<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Constraint;

use function Safe\preg_match;

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
     * @var non-empty-string
     */
    private $cssPattern;

    public function __construct(string $css)
    {
        parent::__construct();

        $this->css = $css;
        $this->cssPattern = '/^\\s*+' . self::getCssRegularExpressionMatcher(\trim($css)) . '\\s*+$/';
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return 'equals CSS `' . $this->css . '`';
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

        return preg_match($this->cssPattern, $other) !== 0;
    }
}
