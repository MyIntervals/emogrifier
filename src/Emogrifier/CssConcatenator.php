<?php

namespace Pelago\Emogrifier;

/**
 * Facilitates building a CSS string by appending rule blocks one at a time, checking whether the media query,
 * selectors, or declarations block are the same as those from the preceding block and combining blocks in such cases.
 *
 * Example:
 *  $concatenator = new CssConcatenator();
 *  $concatenator->append(['body'], 'color: blue;');
 *  $concatenator->append(['body'], 'font-size: 16px;');
 *  $concatenator->append(['p'], 'margin: 1em 0;');
 *  $concatenator->append(['ul', 'ol'], 'margin: 1em 0;');
 *  $concatenator->append(['body'], 'font-size: 14px;', '@media screen and (max-width: 400px)');
 *  $concatenator->append(['ul', 'ol'], 'margin: 0.75em 0;', '@media screen and (max-width: 400px)');
 *  $css = $concatenator->closeBlocksAndGetCss();
 *
 * `$css` (if unminified) would contain the following CSS:
 * ` body {
 * `   color: blue;
 * `   font-size: 16px;
 * ` }
 * ` p, ul, ol {
 * `   margin: 1em 0;
 * ` }
 * ` @media screen and (max-width: 400px) {
 * `   body {
 * `     font-size: 14px;
 * `   }
 * `   ul, ol {
 * `     margin: 0.75em 0;
 * `   }
 * ` }
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 */
class CssConcatenator
{
    /**
     * CSS under construction.
     *
     * @var string
     */
    private $css = '';

    /**
     * Current media query string, e.g. "@media screen and (max-width:639px)" in the currently open media query block,
     * or an empty string if not currently within a media query block.
     *
     * @var string
     */
    private $currentMedia = '';

    /**
     * Array whose keys are selectors for the rule block currently under construction (values are of no significance),
     * or an empty array if no rule block under construction.
     *
     * @var int[]
     */
    private $currentSelectorsAsKeys = [];

    /**
     * Declarations for the rule block currently under construction,
     * or an empty string if no rule block under construction.
     *
     * @var string
     */
    private $currentDeclarationsBlock = '';

    /**
     * Appends a declaration block to the CSS.
     *
     * @param string[] $selectors Array of selectors for the rule, e.g. ["ul", "ol", "p:first-child"].
     * @param string $declarationsBlock The property declarations, e.g. "margin-top: 0.5em; padding: 0".
     * @param string $media The media query for the rule, e.g. "@media screen and (max-width:639px)",
     *                      or an empty string if none.
     */
    public function append(array $selectors, $declarationsBlock, $media = '')
    {
        $selectorsAsKeys = array_flip($selectors);

        if ($media !== $this->currentMedia) {
            $this->closeBlocks();
            if ($media !== '') {
                $this->css .= $media . '{';
                $this->currentMedia = $media;
            }
        }

        if ($declarationsBlock === $this->currentDeclarationsBlock) {
            $this->currentSelectorsAsKeys += $selectorsAsKeys;
        } elseif ($this->hasEquivalentCurrentSelectors($selectorsAsKeys)) {
            $this->currentDeclarationsBlock
                = rtrim(rtrim($this->currentDeclarationsBlock), ';') . ';' . $declarationsBlock;
        } else {
            $this->closeRuleBlock();
            $this->currentSelectorsAsKeys = $selectorsAsKeys;
            $this->currentDeclarationsBlock = $declarationsBlock;
        }
    }

    /**
     * Closes any open rule or media blocks and returns the CSS.
     *
     * @return string
     */
    public function closeBlocksAndGetCss()
    {
        $this->closeBlocks();
        return $this->css;
    }

    /**
     * Closes any open rule or media blocks.
     *
     * @return void
     */
    private function closeBlocks()
    {
        $this->closeRuleBlock();
        if ($this->currentMedia !== '') {
            $this->css .= '}';
            $this->currentMedia = '';
        }
    }

    /**
     * Closes any rule block under construction, appending its contents to the CSS.
     *
     * @return void
     */
    private function closeRuleBlock()
    {
        if ($this->currentSelectorsAsKeys !== [] && $this->currentDeclarationsBlock !== '') {
            $this->css .= implode(',', array_keys($this->currentSelectorsAsKeys))
                . '{' . $this->currentDeclarationsBlock . '}';
        }
        $this->currentSelectorsAsKeys = [];
        $this->currentDeclarationsBlock = '';
    }

    /**
     * Tests if a set of selectors is equivalent to that for the rule block currently under construction
     * (i.e. the same selectors, possibly in a different order).
     *
     * @param int[] $selectorsAsKeys Array in which the selectors are the keys, and the values are of no significance
     *
     * @return bool
     */
    private function hasEquivalentCurrentSelectors(array $selectorsAsKeys)
    {
        return count($selectorsAsKeys) === count($this->currentSelectorsAsKeys)
            && count($selectorsAsKeys) === count($this->currentSelectorsAsKeys + $selectorsAsKeys);
    }
}
