<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Utilities;

/**
 * Parses and stores a CSS document from a string of CSS, and provides methods to obtain the CSS in parts or as data
 * structures.
 *
 * @internal
 */
class CssDocument
{
    /**
     * This regular expression pattern will match any nested at-rule apart from
     * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/At-rule#conditional_group_rules conditional group rules},
     * along with any whitespace immediately following.
     *
     * Currently, only `@media` rules are considered as conditional group rules; others are not yet supported.
     *
     * The first capturing group matches the 'at' sign and identifier (e.g. `@font-face`).  The second capturing group
     * matches the nested statements along with their enclosing curly brackets (i.e. `{...}`), and via `(?2)` will match
     * deeper nested blocks recursively.
     *
     * @var string
     */
    private const NON_CONDITIONAL_AT_RULE_MATCHER
        = '/(@(?!media\\b)[\\w\\-]++)[^\\{]*+(\\{[^\\{\\}]*+(?:(?2)[^\\{\\}]*+)*+\\})\\s*+/i';

    /**
     * Includes regular style rules, and style rules within conditional group rules such as `@media`.
     *
     * @var string
     */
    private $styleRules;

    /**
     * Excludes at-rules which are conditional group rules such as `@media`.  Also excludes `@charset` rules, which are
     * discarded (only UTF-8 is supported).
     *
     * @var string
     */
    private $nonConditionalAtRules;

    /**
     * @param string $css
     */
    public function __construct(string $css)
    {
        $cssWithoutComments = $this->removeCssComments($css);
        [$cssWithoutCommentsCharsetOrImport, $cssImportRules]
            = $this->extractImportAndCharsetRules($cssWithoutComments);
        [$this->styleRules, $cssAtRules]
            = $this->extractNonConditionalAtRules($cssWithoutCommentsCharsetOrImport);
        $this->nonConditionalAtRules = $cssImportRules . $cssAtRules;
    }

    /**
     * Parses the style rules from the CSS into the media query, selectors and declarations for each ruleset.
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
        $splitCss = $this->splitCssAndMediaQuery($allowedMediaTypes);

        $ruleMatches = [];
        foreach ($splitCss as $cssPart) {
            // process each part for selectors and definitions
            \preg_match_all('/(?:^|[\\s^{}]*)([^{]+){([^}]*)}/mi', $cssPart['css'], $matches, PREG_SET_ORDER);

            foreach ($matches as $cssRule) {
                $ruleMatches[] = [
                    'media' => $cssPart['media'],
                    'selectors' => $cssRule[1],
                    'declarations' => $cssRule[2],
                ];
            }
        }

        return $ruleMatches;
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
        return $this->nonConditionalAtRules;
    }

    /**
     * Removes comments from the supplied CSS.
     *
     * @param string $css
     *
     * @return string CSS with the comments removed
     */
    private function removeCssComments(string $css): string
    {
        return \preg_replace('%/\\*[^*]*+(?:\\*(?!/)[^*]*+)*+\\*/%', '', $css);
    }

    /**
     * Extracts `@import` and `@charset` rules from the supplied CSS.  These rules must not be preceded by any other
     * rules, or they will be ignored.  (From the CSS 2.1 specification: "CSS 2.1 user agents must ignore any '@import'
     * rule that occurs inside a block or after any non-ignored statement other than an @charset or an @import rule."
     * Note also that `@charset` is case sensitive whereas `@import` is not.)
     *
     * @param string $css CSS with comments removed
     *
     * @return array{0: string, 1: string}
     *         The first element is the CSS with the valid `@import` and `@charset` rules removed.  The second element
     *         contains a concatenation of the valid `@import` rules, each followed by whatever whitespace followed it
     *         in the original CSS (so that either unminified or minified formatting is preserved); if there were no
     *         `@import` rules, it will be an empty string.  The (valid) `@charset` rules are discarded.
     */
    private function extractImportAndCharsetRules(string $css): array
    {
        $possiblyModifiedCss = $css;
        $importRules = '';

        while (
            \preg_match(
                '/^\\s*+(@((?i)import(?-i)|charset)\\s[^;]++;\\s*+)/',
                $possiblyModifiedCss,
                $matches
            )
        ) {
            [$fullMatch, $atRuleAndFollowingWhitespace, $atRuleName] = $matches;

            if (\strtolower($atRuleName) === 'import') {
                $importRules .= $atRuleAndFollowingWhitespace;
            }

            $possiblyModifiedCss = \substr($possiblyModifiedCss, \strlen($fullMatch));
        }

        return [$possiblyModifiedCss, $importRules];
    }

    /**
     * Extracts at-rules with nested statements (i.e. a block enclosed in curly brackets) from the supplied CSS, with
     * the exception of conditional group rules.  Currently, `@media` is the only supported conditional group rule;
     * others will be extracted by this method.
     *
     * These rules can be placed anywhere in the CSS and are not case sensitive.
     *
     * `@font-face` rules will be checked for validity, though other at-rules will be assumed to be valid.
     *
     * @param string $css CSS with comments, `@import` and `@charset` removed
     *
     * @return array{0: string, 1: string}
     *         The first element is the CSS with the at-rules removed.  The second element contains a concatenation of
     *         the valid at-rules, each followed by whatever whitespace followed it in the original CSS (so that either
     *         unminified or minified formatting is preserved); if there were no at-rules, it will be an empty string.
     */
    private function extractNonConditionalAtRules(string $css): array
    {
        $possiblyModifiedCss = $css;
        $atRules = '';

        while (
            \preg_match(
                self::NON_CONDITIONAL_AT_RULE_MATCHER,
                $possiblyModifiedCss,
                $matches
            )
        ) {
            [$fullMatch, $atRuleName] = $matches;

            if ($this->isValidAtRule($atRuleName, $fullMatch)) {
                $atRules .= $fullMatch;
            }

            $possiblyModifiedCss = \str_replace($fullMatch, '', $possiblyModifiedCss);
        }

        return [$possiblyModifiedCss, $atRules];
    }

    /**
     * Tests if an at-rule is valid.  Currently only `@font-face` rules are checked for validity; others are assumed to
     * be valid.
     *
     * @param string $atIdentifier name of the at-rule with the preceding at sign
     * @param string $rule full content of the rule, including the at-identifier
     *
     * @return bool
     */
    private function isValidAtRule(string $atIdentifier, string $rule): bool
    {
        if (\strcasecmp($atIdentifier, '@font-face') === 0) {
            return \stripos($rule, 'font-family') !== false && \stripos($rule, 'src') !== false;
        }

        return true;
    }

    /**
     * Splits input CSS code into an array of parts for different media queries, in order.
     * Each part is an array where:
     *
     * - key "css" will contain clean CSS code (for @media rules this will be the group rule body within "{...}")
     * - key "media" will contain "@media " followed by the media query list, for all allowed media queries,
     *   or an empty string for CSS not within a media query
     *
     * Example:
     *
     * The CSS code
     *
     *   "@import "file.css"; h1 { color:red; } @media { h1 {}} @media tv { h1 {}}"
     *
     * will be parsed into the following array:
     *
     *   0 => [
     *     "css" => "h1 { color:red; }",
     *     "media" => ""
     *   ],
     *   1 => [
     *     "css" => " h1 {}",
     *     "media" => "@media "
     *   ]
     *
     * @param array<array-key, string> $allowedMediaTypes
     *
     * @return array<int, array{css: string, media: string}>
     */
    private function splitCssAndMediaQuery(array $allowedMediaTypes): array
    {
        $mediaTypesExpression = '';
        if (!empty($allowedMediaTypes)) {
            $mediaTypesExpression = '|' . \implode('|', $allowedMediaTypes);
        }

        $mediaRuleBodyMatcher = '[^{]*+{(?:[^{}]*+{.*})?\\s*+}\\s*+';

        $cssSplitForAllowedMediaTypes = \preg_split(
            '#(@media\\s++(?:only\\s++)?+(?:(?=[{(])' . $mediaTypesExpression . ')' . $mediaRuleBodyMatcher
            . ')#misU',
            $this->styleRules,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        // filter the CSS outside/between allowed @media rules
        $cssCleaningMatchers = [
            'import/charset directives' => '/\\s*+@(?:import|charset)\\s[^;]++;/i',
            'remaining media enclosures' => '/\\s*+@media\\s' . $mediaRuleBodyMatcher . '/isU',
        ];

        $splitCss = [];
        foreach ($cssSplitForAllowedMediaTypes as $index => $cssPart) {
            $isMediaRule = $index % 2 !== 0;
            if ($isMediaRule) {
                \preg_match('/^([^{]*+){(.*)}[^}]*+$/s', $cssPart, $matches);
                $splitCss[] = [
                    'css' => $matches[2],
                    'media' => $matches[1],
                ];
            } else {
                $cleanedCss = \trim(\preg_replace($cssCleaningMatchers, '', $cssPart));
                if ($cleanedCss !== '') {
                    $splitCss[] = [
                        'css' => $cleanedCss,
                        'media' => '',
                    ];
                }
            }
        }
        return $splitCss;
    }
}
