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
     * @var array<string, array-key>
     */
    private $selectorsAsKeys;

    /**
     * @var string
     */
    private $declarationBlock;

    /**
     * @param array<string, array-key> $selectorsAsKeys
     */
    public function __construct(array $selectorsAsKeys = [], string $declarationBlock = '')
    {
        $this->selectorsAsKeys = $selectorsAsKeys;
        $this->declarationBlock = $declarationBlock;
    }

    /**
     * @return array<string, array-key>
     */
    public function getSelectorsAsKeys(): array
    {
        return $this->selectorsAsKeys;
    }

    /**
     * @param array<string, array-key> $selectorsAsKeys
     */
    public function setSelectorsAsKeys(array $selectorsAsKeys): void
    {
        $this->selectorsAsKeys = $selectorsAsKeys;
    }

    /**
     * @param array<string, array-key> $selectorsAsKeys
     */
    public function addSelectorsAsKeys(array $selectorsAsKeys): void
    {
        $this->selectorsAsKeys += $selectorsAsKeys;
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
