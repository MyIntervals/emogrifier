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
 *  $css = $concatenator->getCss();
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
     * Array of media rules in order.  Each element is an object with the following properties:
     * - string `media` - The media query string, e.g. "@media screen and (max-width:639px)", or an empty string for
     *   rules not within a media query block;
     * - stdClass[] `ruleBlocks` - Array of rule blocks in order, where each element is an object with the following
     *   properties:
     *   - int[] `selectorsAsKeys` - Array whose keys are selectors for the rule block (values are of no significance);
     *   - string `declarationsBlock` - The property declarations, e.g. "margin-top: 0.5em; padding: 0".
     *
     * @var stdClass[]
     */
    private $mediaRules = [];

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
        $lastMediaRule = end($this->mediaRules);
        if ($lastMediaRule !== false && $media === $lastMediaRule->media) {
            $mediaRule = $lastMediaRule;
        } else {
            $mediaRule = (object)[
                'media' => $media,
                'ruleBlocks' => [],
            ];
            $this->mediaRules[] = $mediaRule;
        }

        $lastRuleBlock = end($mediaRule->ruleBlocks);
        $selectorsAsKeys = array_flip($selectors);
        if ($lastRuleBlock !== false && $declarationsBlock === $lastRuleBlock->declarationsBlock) {
            $lastRuleBlock->selectorsAsKeys += $selectorsAsKeys;
        } elseif ($lastRuleBlock !== false
            && $this->hasEquivalentSelectors($selectorsAsKeys, $lastRuleBlock->selectorsAsKeys)
        ) {
            $lastRuleBlock->declarationsBlock
                = rtrim(rtrim($lastRuleBlock->declarationsBlock), ';') . ';' . $declarationsBlock;
        } else {
            $mediaRule->ruleBlocks[] = (object)compact('selectorsAsKeys', 'declarationsBlock');
        }
    }

    /**
     * @return string
     */
    public function getCss()
    {
        $css = '';
        foreach ($this->mediaRules as $mediaRule) {
            if ($mediaRule->media !== '') {
                $css .= $mediaRule->media . '{';
            }
            foreach ($mediaRule->ruleBlocks as $ruleBlock) {
                $css .= implode(',', array_keys($ruleBlock->selectorsAsKeys))
                    . '{' . $ruleBlock->declarationsBlock . '}';
            }
            if ($mediaRule->media !== '') {
                $css .= '}';
            }
        }
        return $css;
    }

    /**
     * Tests if two sets of selectors are equivalent (i.e. the same selectors, possibly in a different order).
     *
     * @param int[] $selectorsAsKeys1 Array in which the selectors are the keys, and the values are of no significance.
     * @param int[] $selectorsAsKeys2 Another such array.
     *
     * @return bool
     */
    private static function hasEquivalentSelectors(array $selectorsAsKeys1, array $selectorsAsKeys2)
    {
        return count($selectorsAsKeys1) === count($selectorsAsKeys2)
            && count($selectorsAsKeys1) === count($selectorsAsKeys1 + $selectorsAsKeys2);
    }
}
