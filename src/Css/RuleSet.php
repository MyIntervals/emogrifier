<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Css;

/**
 * This class represents a CSS rule set as defined in the specs: https://drafts.csswg.org/css2/#rule-sets
 *
 * @internal
 */
final class RuleSet
{
    /**
     * @var array<non-empty-string, int<0, max>>
     */
    private $selectorsAsKeys;

    /**
     * @var string
     */
    private $declarationBlock;

    /**
     * @param list<non-empty-string> $selectors
     */
    public function __construct(array $selectors, string $declarationBlock)
    {
        $this->selectorsAsKeys = \array_flip($selectors);
        $this->declarationBlock = $declarationBlock;
    }

    /**
     * @return list<non-empty-string>
     */
    public function getSelectors(): array
    {
        return \array_keys($this->selectorsAsKeys);
    }

    /**
     * @param list<non-empty-string> $selectors
     */
    public function addSelectors(array $selectors): void
    {
        $this->selectorsAsKeys += \array_flip($selectors);
    }

    /**
     * Tests if a set of selectors is equivalent to those currently represented by the object
     * (i.e. the same selectors, possibly in a different order).
     *
     * @param list<non-empty-string> $selectors
     */
    public function hasEquivalentSelectors(array $selectors): bool
    {
        $selectorsAsKeys = \array_flip($selectors);
        return \count($this->selectorsAsKeys) === \count($selectorsAsKeys)
            && \count($this->selectorsAsKeys) === \count($this->selectorsAsKeys + $selectorsAsKeys);
    }

    public function getDeclarationBlock(): string
    {
        return $this->declarationBlock;
    }

    public function setDeclarationBlock(string $declarationBlock): void
    {
        $this->declarationBlock = $declarationBlock;
    }
}
