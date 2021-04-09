<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Utilities;

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
 * @internal
 */
class CssConcatenator
{
    /**
     * Array of media rules in order.  Each element is an object with the following properties:
     * - string `media` - The media query string, e.g. "@media screen and (max-width:639px)", or an empty string for
     *   rules not within a media query block;
     * - \stdClass[] `ruleBlocks` - Array of rule blocks in order, where each element is an object with the following
     *   properties:
     *   - mixed[] `selectorsAsKeys` - Array whose keys are selectors for the rule block (values are of no
     *     significance);
     *   - string `declarationsBlock` - The property declarations, e.g. "margin-top: 0.5em; padding: 0".
     *
     * @var array<int, \stdClass>
     */
    private $mediaRules = [];

    /**
     * Appends a declaration block to the CSS.
     *
     * @param array<array-key, string> $selectors Array of selectors for the rule, e.g. ["ul", "ol", "p:first-child"].
     * @param string $declarationsBlock The property declarations, e.g. "margin-top: 0.5em; padding: 0".
     * @param string $media The media query for the rule, e.g. "@media screen and (max-width:639px)",
     *        or an empty string if none.
     */
    public function append(array $selectors, string $declarationsBlock, string $media = ''): void
    {
        $selectorsAsKeys = \array_flip($selectors);

        $mediaRule = $this->getOrCreateMediaRuleToAppendTo($media);
        /** @var array<int, \stdClass> $ruleBlocks */
        $ruleBlocks = $mediaRule->ruleBlocks;
        $lastRuleBlock = \end($ruleBlocks);

        $hasSameDeclarationsAsLastRule = $lastRuleBlock instanceof \stdClass
            && $declarationsBlock === $lastRuleBlock->declarationsBlock;
        if ($hasSameDeclarationsAsLastRule) {
            /** @var array<string, array-key> $lastRuleBlock->selectorsAsKeys */
            $lastRuleBlock->selectorsAsKeys += $selectorsAsKeys;
        } else {
            /** @var array<string, array-key> $lastRuleBlockSelectors */
            $lastRuleBlockSelectors = $lastRuleBlock instanceof \stdClass ? $lastRuleBlock->selectorsAsKeys : [];
            $hasSameSelectorsAsLastRule = $lastRuleBlock instanceof \stdClass
                && self::hasEquivalentSelectors($selectorsAsKeys, $lastRuleBlockSelectors);
            if ($hasSameSelectorsAsLastRule) {
                $lastDeclarationsBlockWithoutSemicolon = \rtrim(\rtrim((string)$lastRuleBlock->declarationsBlock), ';');
                $lastRuleBlock->declarationsBlock = $lastDeclarationsBlockWithoutSemicolon . ';' . $declarationsBlock;
            } else {
                /** @var array<array-key, \stdClass> $mediaRule->ruleBlocks */
                $mediaRule->ruleBlocks[] = (object)\compact('selectorsAsKeys', 'declarationsBlock');
            }
        }
    }

    /**
     * @return string
     */
    public function getCss(): string
    {
        return \implode('', \array_map([self::class, 'getMediaRuleCss'], $this->mediaRules));
    }

    /**
     * @param string $media The media query for rules to be appended, e.g. "@media screen and (max-width:639px)",
     *        or an empty string if none.
     *
     * @return \stdClass Object with properties as described for elements of `$mediaRules`.
     */
    private function getOrCreateMediaRuleToAppendTo(string $media): \stdClass
    {
        $lastMediaRule = \end($this->mediaRules);
        if ($lastMediaRule instanceof \stdClass && $media === $lastMediaRule->media) {
            return $lastMediaRule;
        }

        $newMediaRule = (object)[
            'media' => $media,
            'ruleBlocks' => [],
        ];
        $this->mediaRules[] = $newMediaRule;
        return $newMediaRule;
    }

    /**
     * Tests if two sets of selectors are equivalent (i.e. the same selectors, possibly in a different order).
     *
     * @param array<string, mixed> $selectorsAsKeys1 Array in which the selectors are the keys, and the values are of no
     *        significance.
     * @param array<string, mixed> $selectorsAsKeys2 Another such array.
     *
     * @return bool
     */
    private static function hasEquivalentSelectors(array $selectorsAsKeys1, array $selectorsAsKeys2): bool
    {
        return \count($selectorsAsKeys1) === \count($selectorsAsKeys2)
            && \count($selectorsAsKeys1) === \count($selectorsAsKeys1 + $selectorsAsKeys2);
    }

    /**
     * @param \stdClass $mediaRule Object with properties as described for elements of `$mediaRules`.
     *
     * @return string CSS for the media rule.
     */
    private static function getMediaRuleCss(\stdClass $mediaRule): string
    {
        /** @var array<array-key, \stdClass> $ruleBlocks */
        $ruleBlocks = (array)$mediaRule->ruleBlocks;
        $css = \implode('', \array_map([self::class, 'getRuleBlockCss'], $ruleBlocks));
        $media = (string)$mediaRule->media;
        if ($media !== '') {
            $css = $media . '{' . $css . '}';
        }
        return $css;
    }

    /**
     * @param \stdClass $ruleBlock Object with properties as described for elements of the `ruleBlocks` property of
     *        elements of `$mediaRules`.
     *
     * @return string CSS for the rule block.
     */
    private static function getRuleBlockCss(\stdClass $ruleBlock): string
    {
        /** @var array<string, array-key> $selectorsAsKeys */
        $selectorsAsKeys = $ruleBlock->selectorsAsKeys;
        /** @var array<array-key, string> $selectors */
        $selectors = \array_keys($selectorsAsKeys);
        /** @var string $declarationsBlock */
        $declarationsBlock = $ruleBlock->declarationsBlock;
        return \implode(',', $selectors) . '{' . $declarationsBlock . '}';
    }
}
