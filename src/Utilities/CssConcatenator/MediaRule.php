<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Utilities\CssConcatenator;

/**
 * Implementation of media rules for \Pelago\Emogrifier\Utilities\CssConcatenator
 *
 * @internal
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 * @author SignpostMarv
 */
class MediaRule
{
    /**
     * The media query string, e.g. "@media screen and (max-width:639px)", or an empty string for
     *   rules not within a media query block;
     *
     * @var string
     */
    public $media;

    /**
     * Array of rule blocks in order, where each element is an object with the following
     *
     * @var RuleBlock[]
     *
     * @psalm-var array<int, RuleBlock>
     */
    public $ruleBlocks = [];

    /**
     * @param string $media
     * @param RuleBlock[] $ruleBlocks
     *
     * @psalm-param array<int, RuleBlock> $ruleBlocks
     */
    public function __construct(string $media, array $ruleBlocks = [])
    {
        $this->media = $media;
        $this->ruleBlocks = $ruleBlocks;
    }
}
