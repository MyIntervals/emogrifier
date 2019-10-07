<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Utilities\CssConcatenator;

/**
 * Implementation of media rules sub-property for \Pelago\Emogrifier\Utilities\CssConcatenator
 *
 * @internal
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 * @author SignpostMarv
 */
class RuleBlock
{
    /**
     * Array whose keys are selectors for the rule block (values are of no
     *     significance);
     *
     * @var mixed[]
     *
     * @psalm-var array<string, mixed>
     */
    public $selectorsAsKeys = [];

    /**
     * The property declarations, e.g. "margin-top: 0.5em; padding: 0".
     *
     * @var string
     */
    public $declarationsBlock;

    /**
     * @param mixed[] $selectorsAsKeys
     * @param string $declarationsBlock
     *
     * @psalm-param array<string, mixed> $selectorsAsKeys
     */
    public function __construct(array $selectorsAsKeys, string $declarationsBlock)
    {
        $this->selectorsAsKeys = $selectorsAsKeys;
        $this->declarationsBlock = $declarationsBlock;
    }
}
