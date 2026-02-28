<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Css;

use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Property\Selector;
use Sabberworm\CSS\RuleSet\DeclarationBlock;

/**
 * This class represents a CSS style rule, including selectors, a declaration block, and an optional containing at-rule.
 *
 * @internal
 */
final class StyleRule
{
    /**
     * @var DeclarationBlock
     */
    private $declarationBlock;

    /**
     * @var string
     */
    private $containingAtRule;

    /**
     * @param string $containingAtRule e.g. `@media screen and (max-width: 480px)`
     */
    public function __construct(DeclarationBlock $declarationBlock, string $containingAtRule = '')
    {
        $this->declarationBlock = $declarationBlock;
        $this->containingAtRule = \trim($containingAtRule);
    }

    /**
     * @return list<non-empty-string> the selectors, e.g. `["h1", "p"]`
     */
    public function getSelectors(): array
    {
        $selectors = $this->declarationBlock->getSelectors();
        return \array_map(
            static function (Selector $selector): string {
                $renderedSelector = $selector->render(OutputFormat::createCompact());
                \assert($renderedSelector !== '');
                return $renderedSelector;
            },
            $selectors
        );
    }

    /**
     * @return string the CSS declarations, separated and followed by a semicolon, e.g., `color: red; height: 4px;`
     */
    public function getDeclarationsAsText(): string
    {
        $declarations = $this->declarationBlock->getDeclarations();
        $renderedDeclarations = [];
        $outputFormat = OutputFormat::create();
        foreach ($declarations as $declaration) {
            $renderedDeclarations[] = $declaration->render($outputFormat);
        }

        return \implode(' ', $renderedDeclarations);
    }

    /**
     * Checks whether the declaration block has at least one declaration.
     */
    public function hasAtLeastOneDeclaration(): bool
    {
        return $this->declarationBlock->getDeclarations() !== [];
    }

    /**
     * @return string e.g. `@media screen and (max-width: 480px)`, or an empty string
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
}
