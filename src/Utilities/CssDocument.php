<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Utilities;

use Sabberworm\CSS\CSSList\AtRuleBlockList as CssAtRuleBlockList;
use Sabberworm\CSS\CSSList\Document as SabberwormCssDocument;
use Sabberworm\CSS\Parser as CssParser;
use Sabberworm\CSS\Property\AtRule as CssAtRule;
use Sabberworm\CSS\Property\Charset as CssCharset;
use Sabberworm\CSS\Property\Import as CssImport;
use Sabberworm\CSS\Renderable as CssRenderable;
use Sabberworm\CSS\RuleSet\DeclarationBlock as CssDeclarationBlock;
use Sabberworm\CSS\RuleSet\RuleSet as CssRuleSet;

/**
 * Parses and stores a CSS document from a string of CSS, and provides methods to obtain the CSS in parts or as data
 * structures.
 *
 * @internal
 */
class CssDocument
{
    /**
     * @var SabberwormCssDocument
     */
    private $sabberwormCssDocument;

    /**
     * `@import` rules must precede all other types of rules, except `@charset` rules.  This property is used while
     * rendering at-rules to enforce that.
     *
     * @var bool
     */
    private $isImportRuleAllowed = true;

    /**
     * @param string $css
     */
    public function __construct(string $css)
    {
        $cssParser = new CssParser($css);
        /** @var SabberwormCssDocument $sabberwormCssDocument */
        $sabberwormCssDocument = $cssParser->parse();
        $this->sabberwormCssDocument = $sabberwormCssDocument;
    }

    /**
     * Collates the media query, selectors and declarations for individual rules from the parsed CSS, in order.
     *
     * @param array<array-key, string> $allowedMediaTypes
     *
     * @return array<int, array{media: string, selectors: string, declarations: string}>
     *         Array of string sub-arrays with the following keys:
     *         - "media" (the media query string, e.g. "@media screen and (max-width: 480px)",
     *           or an empty string if not from an `@media` rule);
     *         - "selectors" (the CSS selector(s), e.g., "*" or "h1, h2");
     *         - "declarations" (the semicolon-separated CSS declarations for that/those selector(s),
     *           e.g., "color: red; height: 4px;").
     */
    public function getStyleRulesData(array $allowedMediaTypes): array
    {
        $ruleMatches = [];
        /** @var CssRenderable $rule */
        foreach ($this->sabberwormCssDocument->getContents() as $rule) {
            if ($rule instanceof CssAtRuleBlockList && $rule->atRuleName() === 'media') {
                /** @var string $mediaQueryList */
                $mediaQueryList = $rule->atRuleArgs();
                [$mediaType] = \explode('(', $mediaQueryList, 2);
                if (\trim($mediaType) !== '') {
                    $mediaTypesExpression = \implode('|', $allowedMediaTypes);
                    if (!\preg_match('/^\\s*+(?:only\\s++)?+(?:' . $mediaTypesExpression . ')/i', $mediaType)) {
                        continue;
                    }
                }
                $media = '@media ' . $mediaQueryList;
                /** @var CssRenderable $nestedRule */
                foreach ($rule->getContents() as $nestedRule) {
                    if ($nestedRule instanceof CssDeclarationBlock) {
                        $ruleMatches[] = [
                            'media' => $media,
                            'rule' => $nestedRule,
                        ];
                    }
                }
            } elseif ($rule instanceof CssDeclarationBlock) {
                $ruleMatches[] = [
                    'media' => '',
                    'rule' => $rule,
                ];
            }
        }

        return \array_map(
            function (array $ruleMatch): array {
                return [
                    'media' => $ruleMatch['media'],
                    'selectors' => \implode(',', $ruleMatch['rule']->getSelectors()),
                    'declarations' => \implode('', $ruleMatch['rule']->getRules()),
                ];
            },
            $ruleMatches
        );
    }

    /**
     * Renders at-rules from the parsed CSS that are valid and not conditional group rules (i.e. not rules such as
     * `@media` which contain style rules whose data is returned by {@see getStyleRulesData}).  Also does not render
     * `@charset` rules; these are discarded (only UTF-8 is supported).
     *
     * @return string
     */
    public function renderNonConditionalAtRules(): string
    {
        $this->isImportRuleAllowed = true;
        /** @var array<int, CssRenderable> $cssContents */
        $cssContents = $this->sabberwormCssDocument->getContents();
        $atRules = \array_filter($cssContents, [$this, 'isValidAtRuleToRender']);

        if ($atRules === []) {
            return '';
        }

        $atRulesDocument = new SabberwormCssDocument();
        $atRulesDocument->setContents($atRules);

        /** @var string $renderedRules */
        $renderedRules = $atRulesDocument->render();
        return $renderedRules;
    }

    /**
     * Tests if a CSS rule is an at-rule that should be passed though and copied to a `<style>` element unmodified:
     * - `@charset` rules are discarded - only UTF-8 is supported - `false` is returned;
     * - `@import` rules are passed through only if they satisfy the specification ("user agents must ignore any
     *   '@import' rule that occurs inside a block or after any non-ignored statement other than an '@charset' or an
     *   '@import' rule");
     * - `@media` rules are processed separately to see if their nested rules apply - `false` is returned;
     * - `@font-face` rules are checked for validity - they must contain both a `src` and `font-family` property;
     * - other at-rules are assumed to be valid and treated as a black box - `true` is returned.
     *
     * @param CssRenderable $rule
     *
     * @return bool
     */
    private function isValidAtRuleToRender(CssRenderable $rule): bool
    {
        if ($rule instanceof CssCharset) {
            return false;
        }

        if ($rule instanceof CssImport) {
            return $this->isImportRuleAllowed;
        }

        $this->isImportRuleAllowed = false;

        if (!$rule instanceof CssAtRule) {
            return false;
        }

        switch ($rule->atRuleName()) {
            case 'media':
                return false;
            case 'font-face':
                return $rule instanceof CssRuleSet
                    && $rule->getRules('font-family') !== []
                    && $rule->getRules('src') !== [];
            default:
                return true;
        }
    }
}
