<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Utilities;

use Pelago\Emogrifier\Css\RuleBlock;

/**
 * Facilitates building a CSS string by appending rule blocks one at a time, checking whether the media query,
 * selectors, or declarations block are the same as those from the preceding block and combining blocks in such cases.
 *
 * Example:
 *
 * ```php
 * $concatenator = new CssConcatenator();
 * $concatenator->append(['body'], 'color: blue;');
 * $concatenator->append(['body'], 'font-size: 16px;');
 * $concatenator->append(['p'], 'margin: 1em 0;');
 * $concatenator->append(['ul', 'ol'], 'margin: 1em 0;');
 * $concatenator->append(['body'], 'font-size: 14px;', '@media screen and (max-width: 400px)');
 * $concatenator->append(['ul', 'ol'], 'margin: 0.75em 0;', '@media screen and (max-width: 400px)');
 * $css = $concatenator->getCss();
 * ```
 *
 * `$css` (if unminified) would contain the following CSS:
 *
 * ```css
 * body {
 *   color: blue;
 *   font-size: 16px;
 * }
 * p, ul, ol {
 *   margin: 1em 0;
 * }
 *
 * @media screen and (max-width: 400px) {
 *   body {
 *     font-size: 14px;
 *   }
 *   ul, ol {
 *     margin: 0.75em 0;
 *   }
 * }
 * ```
 *
 * @internal
 */
final class CssConcatenator
{
    /**
     * Array of media rules in order.  Each element is an object with the following properties:
     * - `media` - The media query string, e.g. `@media screen and (max-width:639px)`, or an empty string for
     *   rules not within a media query block;
     * - `ruleBlocks` - Array of rule blocks in order
     *
     * @var list<object{
     *   media: string,
     *   ruleBlocks: list<RuleBlock>
     * }>
     */
    private $mediaRules = [];

    /**
     * Appends a declaration block to the CSS.
     *
     * @param array<array-key, string> $selectors
     *        array of selectors for the rule, e.g. `["ul", "ol", "p:first-child"]`
     * @param string $declarationsBlock
     *        the property declarations, e.g. `margin-top: 0.5em; padding: 0`
     * @param string $media
     *        the media query for the rule, e.g. `@media screen and (max-width:639px)`, or an empty string if none
     */
    public function append(array $selectors, string $declarationsBlock, string $media = ''): void
    {
        $selectorsAsKeys = \array_flip($selectors);

        $mediaRule = $this->getOrCreateMediaRuleToAppendTo($media);
        $ruleBlocks = $mediaRule->ruleBlocks;
        $lastRuleBlock = \end($ruleBlocks);

        $hasSameDeclarationsAsLastRule = ($lastRuleBlock  instanceof RuleBlock)
            && $declarationsBlock === $lastRuleBlock->getDeclarationsBlock();
        if ($hasSameDeclarationsAsLastRule) {
            $lastRuleBlock->addSelectorsAsKeys($selectorsAsKeys);
        } else {
            $lastRuleBlockSelectors = ($lastRuleBlock instanceof RuleBlock) ? $lastRuleBlock->getSelectorsAsKeys() : [];
            $hasSameSelectorsAsLastRule = ($lastRuleBlock instanceof RuleBlock)
                && self::hasEquivalentSelectors($selectorsAsKeys, $lastRuleBlockSelectors);
            if ($hasSameSelectorsAsLastRule) {
                $lastDeclarationsBlockWithoutSemicolon = \rtrim(\rtrim($lastRuleBlock->getDeclarationsBlock()), ';');
                $lastRuleBlock->setDeclarationsBlock($lastDeclarationsBlockWithoutSemicolon . ';' . $declarationsBlock);
            } else {
                $ruleBlock = new RuleBlock();
                $ruleBlock->setSelectorsAsKeys($selectorsAsKeys);
                $ruleBlock->setDeclarationsBlock($declarationsBlock);
                $mediaRule->ruleBlocks[] = $ruleBlock;
            }
        }
    }

    public function getCss(): string
    {
        return \implode('', \array_map([self::class, 'getMediaRuleCss'], $this->mediaRules));
    }

    /**
     * @param string $media The media query for rules to be appended, e.g. `@media screen and (max-width:639px)`,
     *        or an empty string if none.
     *
     * @return object{
     *           media: string,
     *           ruleBlocks: list<RuleBlock>
     *         }
     */
    private function getOrCreateMediaRuleToAppendTo(string $media): object
    {
        $lastMediaRule = \end($this->mediaRules);
        if (\is_object($lastMediaRule) && $media === $lastMediaRule->media) {
            return $lastMediaRule;
        }

        $newMediaRule = (object) [
            'media' => $media,
            'ruleBlocks' => [],
        ];
        $this->mediaRules[] = $newMediaRule;

        return $newMediaRule;
    }

    /**
     * Tests if two sets of selectors are equivalent (i.e. the same selectors, possibly in a different order).
     *
     * @param array<string, array-key> $selectorsAsKeys1
     *        array in which the selectors are the keys, and the values are of no significance
     * @param array<string, array-key> $selectorsAsKeys2 another such array
     */
    private static function hasEquivalentSelectors(array $selectorsAsKeys1, array $selectorsAsKeys2): bool
    {
        return \count($selectorsAsKeys1) === \count($selectorsAsKeys2)
            && \count($selectorsAsKeys1) === \count($selectorsAsKeys1 + $selectorsAsKeys2);
    }

    /**
     * @param object{
     *          media: string,
     *          ruleBlocks: array<int, RuleBlock>
     *        } $mediaRule
     */
    private static function getMediaRuleCss(object $mediaRule): string
    {
        $ruleBlocks = $mediaRule->ruleBlocks;
        $css = \implode('', \array_map([self::class, 'getRuleBlockCss'], $ruleBlocks));
        $media = $mediaRule->media;
        if ($media !== '') {
            $css = $media . '{' . $css . '}';
        }

        return $css;
    }

    private static function getRuleBlockCss(RuleBlock $ruleBlock): string
    {
        $selectorsAsKeys = $ruleBlock->getSelectorsAsKeys();
        $selectors = \array_keys($selectorsAsKeys);
        $declarationsBlock = $ruleBlock->getDeclarationsBlock();

        return \implode(',', $selectors) . '{' . $declarationsBlock . '}';
    }
}
