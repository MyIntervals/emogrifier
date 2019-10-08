<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Utilities\CssConcatenator;

/**
 * This class represents a CSS rule block, i.e., one ore multiple selectors (like "p") and a declarations block
 * (like "margin-top: 2pc").
 *
 * @internal
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 * @author SignpostMarv
 * @author Oliver Klee <github@oliverklee.de>
 */
class RuleBlock
{
    /**
     * selectors for the rule block
     *
     * @var string[]
     */
    private $selectors = [];

    /**
     * Array whose keys are selectors for the rule block (values are of no significance).
     * This is redundant to `$selectors`; the data is stored twice for performance reasons.
     *
     * @var array
     *
     * @psalm-var array<string, mixed>
     */
    private $selectorsAsKeys = [];

    /**
     * the property declarations, e.g. "margin-top: 0.5em;padding: 0"
     *
     * @var string
     */
    private $declarationsBlock;

    /**
     * @param string[] $selectors
     * @param string $declarationsBlock
     *
     * @psalm-param array<string, mixed> $selectorsAsKeys
     */
    public function __construct(array $selectors, string $declarationsBlock)
    {
        $this->selectors = $selectors;
        $this->selectorsAsKeys = \array_flip($selectors);
        $this->declarationsBlock = $declarationsBlock;
    }

    /**
     * @return string[]
     */
    public function getSelectors(): array
    {
        return $this->selectors;
    }

    /**
     * @return array
     *
     * @psalm-return array<string, mixed>
     */
    public function getSelectorsAsKeys(): array
    {
        return $this->selectorsAsKeys;
    }

    /**
     * @return string
     */
    public function getDeclarationsBlock(): string
    {
        return $this->declarationsBlock;
    }

    /**
     * @return string CSS for the rule block
     */
    public function getCss(): string
    {
        return \implode(',', $this->selectors) . '{' . $this->declarationsBlock . '}';
    }

    /**
     * @param RuleBlock $otherRuleBlock
     *
     * @return bool
     */
    public function hasEquivalentSelectors(RuleBlock $otherRuleBlock): bool
    {
        $ownSelectorCount = $this->getSelectorCount();
        if ($ownSelectorCount !== $otherRuleBlock->getSelectorCount()) {
            return false;
        }

        if ($this->getSelectorsAsKeys() === $otherRuleBlock->getSelectorsAsKeys()) {
            return true;
        }

        $combinedSelectorKeys = $this->getSelectorsAsKeys() + $otherRuleBlock->getSelectorsAsKeys();

        return $ownSelectorCount === \count($combinedSelectorKeys);
    }

    /**
     * @return int
     */
    private function getSelectorCount(): int
    {
        return \count($this->selectors);
    }
}
