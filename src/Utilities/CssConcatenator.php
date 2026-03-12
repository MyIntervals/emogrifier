<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Utilities;

use Pelago\Emogrifier\Css\RuleSet;
use Pelago\Emogrifier\Css\RuleSetList;

/**
 * Facilitates building a CSS string by appending rulesets one at a time,
 * checking whether the enclosing at-rule (if any), selectors, or declaration block
 * are the same as those from the preceding rule and combining rules in such cases.
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
     * Each ruleset list will have a different at-rule.
     * Within each list will be rulesets with different selectors or declaration blocks.
     *
     * @var list<RuleSetList>
     */
    private $ruleSetLists = [];

    /**
     * Appends a ruleset to the CSS.
     *
     * @param non-empty-list<non-empty-string> $selectors
     *        array of selectors for the rule, e.g. `["ul", "ol", "p:first-child"]`
     * @param string $declarationBlock
     *        the property declarations, e.g. `margin-top: 0.5em; padding: 0`
     * @param string $atRule
     *        optional name and parameter of an enclosing at-rule, e.g. `@media screen and (max-width:639px)`;
     *        an empty string if the ruleset is not within an at-rule
     */
    public function append(array $selectors, string $declarationBlock, string $atRule = ''): void
    {
        $ruleSetList = $this->getOrCreateRuleSetListToAppendTo($atRule);
        $ruleSets = $ruleSetList->getRuleSets();
        $lastRuleSet = \end($ruleSets);

        $hasSameDeclarationsAsLastRule = ($lastRuleSet instanceof RuleSet)
            && $declarationBlock === $lastRuleSet->getDeclarationBlock();
        if ($hasSameDeclarationsAsLastRule) {
            $lastRuleSet->addSelectors($selectors);
        } else {
            $hasSameSelectorsAsLastRule = ($lastRuleSet instanceof RuleSet)
                && $lastRuleSet->hasEquivalentSelectors($selectors);
            if ($hasSameSelectorsAsLastRule) {
                $lastDeclarationBlockWithoutSemicolon = \rtrim(\rtrim($lastRuleSet->getDeclarationBlock()), ';');
                $lastRuleSet->setDeclarationBlock($lastDeclarationBlockWithoutSemicolon . ';' . $declarationBlock);
            } else {
                $ruleSetList->appendRuleSet(new RuleSet($selectors, $declarationBlock));
            }
        }
    }

    public function getCss(): string
    {
        return \implode('', \array_map([self::class, 'getRuleSetListCss'], $this->ruleSetLists));
    }

    /**
     * @param string $atRule
     *        optional name and parameter of an enclosing at-rule, e.g. `@media screen and (max-width:639px)`;
     *        an empty string if the rulesets to be appended are not within an at-rule
     */
    private function getOrCreateRuleSetListToAppendTo(string $atRule): RuleSetList
    {
        $lastRuleSetList = \end($this->ruleSetLists);
        if ($lastRuleSetList instanceof RuleSetList && $atRule === $lastRuleSetList->getAtRule()) {
            return $lastRuleSetList;
        }

        $newRuleSetList = new RuleSetList($atRule);
        $this->ruleSetLists[] = $newRuleSetList;

        return $newRuleSetList;
    }

    private static function getRuleSetListCss(RuleSetList $ruleSetList): string
    {
        $ruleSets = $ruleSetList->getRuleSets();
        $css = \implode('', \array_map([self::class, 'getRuleSetCss'], $ruleSets));
        $atRule = $ruleSetList->getAtRule();
        if ($atRule !== '') {
            $css = $atRule . '{' . $css . '}';
        }

        return $css;
    }

    private static function getRuleSetCss(RuleSet $ruleSet): string
    {
        $selectors = $ruleSet->getSelectors();
        $declarationBlock = $ruleSet->getDeclarationBlock();

        return \implode(',', $selectors) . '{' . $declarationBlock . '}';
    }
}
