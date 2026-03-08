<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Css;

/**
 * @internal
 */
final class RuleBlock
{
    /**
     * @var array<string, array-key>
     */
    private $selectorsAsKeys = [];

    /**
     * @var string
     */
    private $declarationsBlock = '';

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

    public function getDeclarationsBlock(): string
    {
        return $this->declarationsBlock;
    }

    public function setDeclarationsBlock(string $declarationsBlock): void
    {
        $this->declarationsBlock = $declarationsBlock;
    }
}
