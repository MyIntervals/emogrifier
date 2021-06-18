<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Css;

/**
 * This class represents a CSS style rule, including selectors, a declaration block, and a containing at-rule.
 *
 * @internal
 */
class StyleRule
{
    /**
     * @var string
     */
    private $containingAtRule;

    /**
     * @var array<int, string>
     */
    private $selectors;

    /**
     * @var string
     */
    private $declarationBlock;

    /**
     * @param string $containingAtRule e.g. `@media screen and (max-width: 480px)`, or an empty string
     * @param string $selectors the comma-separated selectors, e.g., `*` or `h1, h2`, must not be empty
     * @param string $declarationBlock the comma-separated declarations, e.g., `color: red; height: 4px;`, may be empty
     *
     * @throws \InvalidArgumentException if $selectors is empty (or whitespace only)
     */
    public function __construct(string $containingAtRule, string $selectors, string $declarationBlock)
    {
        if (\trim($selectors) === '') {
            throw new \InvalidArgumentException('Please provide non-empty selectors.', 1623263716);
        }

        $this->containingAtRule = \trim($containingAtRule);
        $this->selectors = \array_map('trim', \explode(',', $selectors));
        $this->declarationBlock = \trim($declarationBlock);
    }

    /**
     * @returns string e.g. `@media screen and (max-width: 480px)`, or an empty string
     */
    public function getContainingAtRule(): string
    {
        return $this->containingAtRule;
    }

    /**
     * Checks whether the containing at-rule is non-empty and has any non-whitespace characters.
     */
    public function hasContainingAtRule(): bool
    {
        return $this->getContainingAtRule() !== '';
    }

    /**
     * @return array<int, string> the selectors, e.g, `["h1", "h2"]`
     */
    public function getSelectors(): array
    {
        return $this->selectors;
    }

    /**
     * @return string the semicolon-separated CSS declarations, e.g., `color: red; height: 4px;`
     */
    public function getDeclarationBlock(): string
    {
        return $this->declarationBlock;
    }

    /**
     * Checks whether the declaration block has at least one non-empty declaration.
     */
    public function hasAtLeastOneDeclaration(): bool
    {
        return $this->getDeclarationBlock() !== '';
    }
}
