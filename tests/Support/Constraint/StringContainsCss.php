<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Constraint;

/**
 * This constraint asserts that the string it is evaluated for contains some specific CSS, allowing for cosmetic
 * whitespace differences.
 *
 * The CSS string passed in the constructor.
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 */
class StringContainsCss extends CssConstraint
{
    /**
     * @var string
     */
    private $css;

    /**
     * @param string $css
     */
    public function __construct(string $css)
    {
        parent::__construct();

        $this->css = $css;
    }

    /**
     * @inheritdoc
     */
    public function toString(): string
    {
        return 'contains CSS `' . $this->css . '`';
    }

    /**
     * @inheritdoc
     *
     * @param mixed $other
     */
    protected function matches($other): bool
    {
        return \preg_match(self::getCssNeedleRegularExpressionPattern($this->css), $other) > 0;
    }
}
