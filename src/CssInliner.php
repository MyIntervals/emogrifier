<?php

declare(strict_types=1);

namespace Pelago\Emogrifier;

use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\Utilities\CssConcatenator;
use Sabberworm\CSS\CSSList\AtRuleBlockList as CssAtRuleBlockList;
use Sabberworm\CSS\CSSList\Document as CssDocument;
use Sabberworm\CSS\Parser as CssParser;
use Sabberworm\CSS\Property\AtRule as CssAtRule;
use Sabberworm\CSS\Property\Charset as CssCharset;
use Sabberworm\CSS\Property\Import as CssImport;
use Sabberworm\CSS\Renderable as CssRenderable;
use Sabberworm\CSS\RuleSet\DeclarationBlock as CssDeclarationBlock;
use Sabberworm\CSS\RuleSet\RuleSet as CssRuleSet;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\ParseException;

/**
 * This class provides functions for converting CSS styles into inline style attributes in your HTML code.
 *
 * For Emogrifier 3.0.0, this will be the successor to the \Pelago\Emogrifier class (which then will be deprecated).
 *
 * For more information, please see the README.md file.
 *
 * @author Cameron Brooks
 * @author Jaime Prado
 * @author Oliver Klee <github@oliverklee.de>
 * @author Roman Ožana <ozana@omdesign.cz>
 * @author Sander Kruger <s.kruger@invessel.com>
 * @author Zoli Szabó <zoli.szabo+github@gmail.com>
 */
class CssInliner extends AbstractHtmlProcessor
{
    /**
     * @var int
     */
    private const CACHE_KEY_CSS = 0;

    /**
     * @var int
     */
    private const CACHE_KEY_SELECTOR = 1;

    /**
     * @var int
     */
    private const CACHE_KEY_CSS_DECLARATIONS_BLOCK = 2;

    /**
     * @var int
     */
    private const CACHE_KEY_COMBINED_STYLES = 3;

    /**
     * This regular expression pattern will match any uninlinable at-rule with nested statements, along with any
     * whitespace immediately following.  Currently, any at-rule apart from `@media` is considered uninlinable.  The
     * first capturing group matches the at sign and identifier (e.g. `@font-face`).  The second capturing group matches
     * the nested statements along with their enclosing curly brackets (i.e. `{...}`), and via `(?2)` will match deeper
     * nested blocks recursively.
     *
     * @var string
     */
    private const UNINLINABLE_AT_RULE_MATCHER
        = '/(@(?!media\\b)[\\w\\-]++)[^\\{]*+(\\{[^\\{\\}]*+(?:(?2)[^\\{\\}]*+)*+\\})\\s*+/i';

    /**
     * Regular expression component matching a static pseudo class in a selector, without the preceding ":",
     * for which the applicable elements can be determined (by converting the selector to an XPath expression).
     * (Contains alternation without a group and is intended to be placed within a capturing, non-capturing or lookahead
     * group, as appropriate for the usage context.)
     *
     * @var string
     */
    private const PSEUDO_CLASS_MATCHER
        = 'empty|(?:first|last|nth(?:-last)?+|only)-(?:child|of-type)|not\\([[:ascii:]]*\\)';

    /**
     * This regular expression componenet matches an `...of-type` pseudo class name, without the preceding ":".  These
     * pseudo-classes can currently online be inlined if they have an associated type in the selector expression.
     *
     * @var string
     */
    private const OF_TYPE_PSEUDO_CLASS_MATCHER = '(?:first|last|nth(?:-last)?+|only)-of-type';

    /**
     * regular expression component to match a selector combinator
     *
     * @var string
     */
    private const COMBINATOR_MATCHER = '(?:\\s++|\\s*+[>+~]\\s*+)(?=[[:alpha:]_\\-.#*:\\[])';

    /**
     * @var bool[]
     */
    private $excludedSelectors = [];

    /**
     * @var bool[]
     */
    private $allowedMediaTypes = ['all' => true, 'screen' => true, 'print' => true];

    /**
     * @var mixed[]
     */
    private $caches = [
        self::CACHE_KEY_CSS => [],
        self::CACHE_KEY_SELECTOR => [],
        self::CACHE_KEY_CSS_DECLARATIONS_BLOCK => [],
        self::CACHE_KEY_COMBINED_STYLES => [],
    ];

    /**
     * @var CssSelectorConverter
     */
    private $cssSelectorConverter = null;

    /**
     * `@import` rules must precede all other types of rules, except `@charset` rules.  This property is used while
     * rendering uninlinable at-rules to enforce that.
     *
     * @var bool
     */
    private $isImportRuleAllowed = true;

    /**
     * the visited nodes with the XPath paths as array keys
     *
     * @var \DOMElement[]
     */
    private $visitedNodes = [];

    /**
     * the styles to apply to the nodes with the XPath paths as array keys for the outer array
     * and the attribute names/values as key/value pairs for the inner array
     *
     * @var string[][]
     */
    private $styleAttributesForNodes = [];

    /**
     * Determines whether the "style" attributes of tags in the the HTML passed to this class should be preserved.
     * If set to false, the value of the style attributes will be discarded.
     *
     * @var bool
     */
    private $isInlineStyleAttributesParsingEnabled = true;

    /**
     * Determines whether the <style> blocks in the HTML passed to this class should be parsed.
     *
     * If set to true, the <style> blocks will be removed from the HTML and their contents will be applied to the HTML
     * via inline styles.
     *
     * If set to false, the <style> blocks will be left as they are in the HTML.
     *
     * @var bool
     */
    private $isStyleBlocksParsingEnabled = true;

    /**
     * For calculating selector precedence order.
     * Keys are a regular expression part to match before a CSS name.
     * Values are a multiplier factor per match to weight specificity.
     *
     * @var int[]
     */
    private $selectorPrecedenceMatchers = [
        // IDs: worth 10000
        '\\#' => 10000,
        // classes, attributes, pseudo-classes (not pseudo-elements) except `:not`: worth 100
        '(?:\\.|\\[|(?<!:):(?!not\\())' => 100,
        // elements (not attribute values or `:not`), pseudo-elements: worth 1
        '(?:(?<![="\':\\w\\-])|::)' => 1,
    ];

    /**
     * array of data describing CSS rules which apply to the document but cannot be inlined, in the format returned by
     * `parseCssRules`
     *
     * @var (string|bool|int)[][]
     */
    private $matchingUninlinableCssRules = null;

    /**
     * Emogrifier will throw Exceptions when it encounters an error instead of silently ignoring them.
     *
     * @var bool
     */
    private $debug = false;

    /**
     * Inlines the given CSS into the existing HTML.
     *
     * @param string $css the CSS to inline, must be UTF-8-encoded
     *
     * @return self fluent interface
     *
     * @throws ParseException in debug mode, if an invalid selector is encountered
     * @throws \RuntimeException in debug mode, if an internal PCRE error occurs
     */
    public function inlineCss(string $css = ''): self
    {
        $this->clearAllCaches();
        $this->purgeVisitedNodes();

        $this->normalizeStyleAttributesOfAllNodes();

        $combinedCss = $css;
        // grab any existing style blocks from the HTML and append them to the existing CSS
        // (these blocks should be appended so as to have precedence over conflicting styles in the existing CSS)
        if ($this->isStyleBlocksParsingEnabled) {
            $combinedCss .= $this->getCssFromAllStyleNodes();
        }

        $cssParser = new CssParser($combinedCss);
        /** @psalm-var CssDocument $cssDocument */
        $cssDocument = $cssParser->parse();

        $excludedNodes = $this->getNodesToExclude();
        /** @psalm-var (string|bool|int)[][][] $cssRules */
        $cssRules = $this->collateCssRules($cssDocument);
        $cssSelectorConverter = $this->getCssSelectorConverter();
        foreach ($cssRules['inlinable'] as $cssRule) {
            try {
                /** @psalm-var string $selector */
                $selector = $cssRule['selector'];
                $nodesMatchingCssSelectors = $this->xPath->query($cssSelectorConverter->toXPath($selector));
            } catch (ParseException $e) {
                if ($this->debug) {
                    throw $e;
                }
                continue;
            }

            /** @var \DOMElement $node */
            foreach ($nodesMatchingCssSelectors as $node) {
                if (\in_array($node, $excludedNodes, true)) {
                    continue;
                }
                $this->copyInlinableCssToStyleAttribute($node, $cssRule);
            }
        }

        if ($this->isInlineStyleAttributesParsingEnabled) {
            $this->fillStyleAttributesWithMergedStyles();
        }

        $this->removeImportantAnnotationFromAllInlineStyles();

        $this->determineMatchingUninlinableCssRules($cssRules['uninlinable']);
        $this->copyUninlinableCssToStyleNode($cssDocument);

        return $this;
    }

    /**
     * Disables the parsing of inline styles.
     *
     * @return self fluent interface
     */
    public function disableInlineStyleAttributesParsing(): self
    {
        $this->isInlineStyleAttributesParsingEnabled = false;

        return $this;
    }

    /**
     * Disables the parsing of <style> blocks.
     *
     * @return self fluent interface
     */
    public function disableStyleBlocksParsing(): self
    {
        $this->isStyleBlocksParsingEnabled = false;

        return $this;
    }

    /**
     * Marks a media query type to keep.
     *
     * @param string $mediaName the media type name, e.g., "braille"
     *
     * @return self fluent interface
     */
    public function addAllowedMediaType(string $mediaName): self
    {
        $this->allowedMediaTypes[$mediaName] = true;

        return $this;
    }

    /**
     * Drops a media query type from the allowed list.
     *
     * @param string $mediaName the tag name, e.g., "braille"
     *
     * @return self fluent interface
     */
    public function removeAllowedMediaType(string $mediaName): self
    {
        if (isset($this->allowedMediaTypes[$mediaName])) {
            unset($this->allowedMediaTypes[$mediaName]);
        }

        return $this;
    }

    /**
     * Adds a selector to exclude nodes from emogrification.
     *
     * Any nodes that match the selector will not have their style altered.
     *
     * @param string $selector the selector to exclude, e.g., ".editor"
     *
     * @return self fluent interface
     */
    public function addExcludedSelector(string $selector): self
    {
        $this->excludedSelectors[$selector] = true;

        return $this;
    }

    /**
     * No longer excludes the nodes matching this selector from emogrification.
     *
     * @param string $selector the selector to no longer exclude, e.g., ".editor"
     *
     * @return self fluent interface
     */
    public function removeExcludedSelector(string $selector): self
    {
        if (isset($this->excludedSelectors[$selector])) {
            unset($this->excludedSelectors[$selector]);
        }

        return $this;
    }

    /**
     * Sets the debug mode.
     *
     * @param bool $debug set to true to enable debug mode
     *
     * @return self fluent interface
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Gets the array of selectors present in the CSS provided to `inlineCss()` for which the declarations could not be
     * applied as inline styles, but which may affect elements in the HTML.  The relevant CSS will have been placed in a
     * `<style>` element.  The selectors may include those used within `@media` rules or those involving dynamic
     * pseudo-classes (such as `:hover`) or pseudo-elements (such as `::after`).
     *
     * @return string[]
     *
     * @throws \BadMethodCallException if `inlineCss` has not been called first
     */
    public function getMatchingUninlinableSelectors(): array
    {
        if ($this->matchingUninlinableCssRules === null) {
            throw new \BadMethodCallException('inlineCss must be called first', 1568385221);
        }

        return \array_column($this->matchingUninlinableCssRules, 'selector');
    }

    /**
     * Clears all caches.
     */
    private function clearAllCaches(): void
    {
        $this->caches = [
            self::CACHE_KEY_CSS => [],
            self::CACHE_KEY_SELECTOR => [],
            self::CACHE_KEY_CSS_DECLARATIONS_BLOCK => [],
            self::CACHE_KEY_COMBINED_STYLES => [],
        ];
    }

    /**
     * Purges the visited nodes.
     */
    private function purgeVisitedNodes(): void
    {
        $this->visitedNodes = [];
        $this->styleAttributesForNodes = [];
    }

    /**
     * Parses the document and normalizes all existing CSS attributes.
     * This changes 'DISPLAY: none' to 'display: none'.
     * We wouldn't have to do this if DOMXPath supported XPath 2.0.
     * Also stores a reference of nodes with existing inline styles so we don't overwrite them.
     */
    private function normalizeStyleAttributesOfAllNodes(): void
    {
        /** @var \DOMElement $node */
        foreach ($this->getAllNodesWithStyleAttribute() as $node) {
            if ($this->isInlineStyleAttributesParsingEnabled) {
                $this->normalizeStyleAttributes($node);
            }
            // Remove style attribute in every case, so we can add them back (if inline style attributes
            // parsing is enabled) to the end of the style list, thus keeping the right priority of CSS rules;
            // else original inline style rules may remain at the beginning of the final inline style definition
            // of a node, which may give not the desired results
            $node->removeAttribute('style');
        }
    }

    /**
     * Returns a list with all DOM nodes that have a style attribute.
     *
     * @return \DOMNodeList
     */
    private function getAllNodesWithStyleAttribute(): \DOMNodeList
    {
        return $this->xPath->query('//*[@style]');
    }

    /**
     * Normalizes the value of the "style" attribute and saves it.
     *
     * @param \DOMElement $node
     */
    private function normalizeStyleAttributes(\DOMElement $node): void
    {
        $normalizedOriginalStyle = \preg_replace_callback(
            '/-?+[_a-zA-Z][\\w\\-]*+(?=:)/S',
            static function (array $m) {
                return \strtolower($m[0]);
            },
            $node->getAttribute('style')
        );

        // in order to not overwrite existing style attributes in the HTML, we
        // have to save the original HTML styles
        $nodePath = $node->getNodePath();
        if (!isset($this->styleAttributesForNodes[$nodePath])) {
            $this->styleAttributesForNodes[$nodePath] = $this->parseCssDeclarationsBlock($normalizedOriginalStyle);
            $this->visitedNodes[$nodePath] = $node;
        }

        $node->setAttribute('style', $normalizedOriginalStyle);
    }

    /**
     * Parses a CSS declaration block into property name/value pairs.
     *
     * Example:
     *
     * The declaration block
     *
     *   "color: #000; font-weight: bold;"
     *
     * will be parsed into the following array:
     *
     *   "color" => "#000"
     *   "font-weight" => "bold"
     *
     * @param string $cssDeclarationsBlock the CSS declarations block without the curly braces, may be empty
     *
     * @return string[]
     *         the CSS declarations with the property names as array keys and the property values as array values
     */
    private function parseCssDeclarationsBlock(string $cssDeclarationsBlock): array
    {
        if (isset($this->caches[self::CACHE_KEY_CSS_DECLARATIONS_BLOCK][$cssDeclarationsBlock])) {
            return $this->caches[self::CACHE_KEY_CSS_DECLARATIONS_BLOCK][$cssDeclarationsBlock];
        }

        $properties = [];
        foreach (\preg_split('/;(?!base64|charset)/', $cssDeclarationsBlock) as $declaration) {
            $matches = [];
            if (!\preg_match('/^([A-Za-z\\-]+)\\s*:\\s*(.+)$/s', \trim($declaration), $matches)) {
                continue;
            }

            $propertyName = \strtolower($matches[1]);
            $propertyValue = $matches[2];
            $properties[$propertyName] = $propertyValue;
        }
        $this->caches[self::CACHE_KEY_CSS_DECLARATIONS_BLOCK][$cssDeclarationsBlock] = $properties;

        return $properties;
    }

    /**
     * Returns CSS content.
     *
     * @return string
     */
    private function getCssFromAllStyleNodes(): string
    {
        $styleNodes = $this->xPath->query('//style');
        if ($styleNodes === false) {
            return '';
        }

        $css = '';
        foreach ($styleNodes as $styleNode) {
            $css .= "\n\n" . $styleNode->nodeValue;
            $parentNode = $styleNode->parentNode;
            if ($parentNode instanceof \DOMNode) {
                $parentNode->removeChild($styleNode);
            }
        }

        return $css;
    }

    /**
     * Find the nodes that are not to be emogrified.
     *
     * @return \DOMElement[]
     *
     * @throws ParseException
     */
    private function getNodesToExclude(): array
    {
        $excludedNodes = [];
        foreach (\array_keys($this->excludedSelectors) as $selectorToExclude) {
            try {
                $matchingNodes = $this->xPath->query($this->getCssSelectorConverter()->toXPath($selectorToExclude));
            } catch (ParseException $e) {
                if ($this->debug) {
                    throw $e;
                }
                continue;
            }
            foreach ($matchingNodes as $node) {
                $excludedNodes[] = $node;
            }
        }

        return $excludedNodes;
    }

    /**
     * @return CssSelectorConverter
     */
    private function getCssSelectorConverter(): CssSelectorConverter
    {
        if ($this->cssSelectorConverter === null) {
            $this->cssSelectorConverter = new CssSelectorConverter();
        }

        return $this->cssSelectorConverter;
    }

    /**
     * Collates the individual rules from a `CssDocument` containing parsed CSS.
     *
     * @param CssDocument $cssDocument
     *
     * @psalm-return array {
     *   inlinable: list<array {
     *     declarationsBlock: non-empty-string,
     *     hasUnmatchablePseudo: bool,
     *     line: int,
     *     media: string,
     *     selector: string
     *   } >,
     *   uninlinable: list<array {
     *     declarationsBlock: non-empty-string,
     *     hasUnmatchablePseudo: bool,
     *     line: int,
     *     media: string,
     *     selector: string
     *   } >
     * }
     *
     * @return (string|bool|int)[][][] {
     * This 2-entry array has the key "inlinable" containing rules which can be inlined as `style` attributes and the
     * key "uninlinable" containing rules which cannot.  Each value is an array of sub-arrays with the following keys:
     * @type string $media the media query string, e.g. "@media screen and (max-width: 480px)", or an empty string if
     *       not from a `@media` rule)
     * @type string $selector the CSS selector, e.g., "*" or "header h1"
     * @type bool $hasUnmatchablePseudo `true` if that selector contains pseudo-elements or dynamic pseudo-classes
     *       such that the declarations cannot be applied inline
     * @type string $declarationsBlock the semicolon-separated CSS declarations for that selector, e.g.,
     *       "color: red; height: 4px;"
     * @type int $line the line number, e.g. 42
     * }
     */
    private function collateCssRules(CssDocument $cssDocument): array
    {
        $matches = $this->getCssRuleMatches($cssDocument);

        $cssRules = [
            'inlinable' => [],
            'uninlinable' => [],
        ];
        foreach ($matches as $key => $cssRule) {
            $cssDeclaration = \trim($cssRule['declarations']);
            if ($cssDeclaration === '') {
                continue;
            }

            foreach (\explode(',', $cssRule['selectors']) as $selector) {
                // don't process pseudo-elements and behavioral (dynamic) pseudo-classes;
                // only allow structural pseudo-classes
                $hasPseudoElement = \strpos($selector, '::') !== false;
                $hasUnmatchablePseudo = $hasPseudoElement || $this->hasUnsupportedPseudoClass($selector);

                $parsedCssRule = [
                    'media' => $cssRule['media'],
                    'selector' => \trim($selector),
                    'hasUnmatchablePseudo' => $hasUnmatchablePseudo,
                    'declarationsBlock' => $cssDeclaration,
                    // keep track of where it appears in the file, since order is important
                    'line' => $key,
                ];
                $ruleType = ($cssRule['media'] === '' && !$hasUnmatchablePseudo) ? 'inlinable' : 'uninlinable';
                $cssRules[$ruleType][] = $parsedCssRule;
            }
        }

        \usort($cssRules['inlinable'], [$this, 'sortBySelectorPrecedence']);

        return $cssRules;
    }

    /**
     * Tests if a selector contains a pseudo-class which would mean it cannot be converted to an XPath expression for
     * inlining CSS declarations.
     *
     * Any pseudo class that does not match {@see PSEUDO_CLASS_MATCHER} cannot be converted.  Additionally, `...of-type`
     * pseudo-classes cannot be converted if they are not associated with a type selector.
     *
     * @param string $selector
     *
     * @return bool
     */
    private function hasUnsupportedPseudoClass(string $selector): bool
    {
        if (\preg_match('/:(?!' . self::PSEUDO_CLASS_MATCHER . ')[\\w\\-]/i', $selector)) {
            return true;
        }

        if (!\preg_match('/:(?:' . self::OF_TYPE_PSEUDO_CLASS_MATCHER . ')/i', $selector)) {
            return false;
        }

        foreach (\preg_split('/' . self::COMBINATOR_MATCHER . '/', $selector) as $selectorPart) {
            if ($this->selectorPartHasUnsupportedOfTypePseudoClass($selectorPart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tests if part of a selector contains an `...of-type` pseudo-class such that it cannot be converted to an XPath
     * expression.
     *
     * @param string $selectorPart part of a selector which has been split up at combinators
     *
     * @return bool `true` if the selector part does not have a type but does have an `...of-type` pseudo-class
     */
    private function selectorPartHasUnsupportedOfTypePseudoClass(string $selectorPart): bool
    {
        if (\preg_match('/^[\\w\\-]/', $selectorPart)) {
            return false;
        }

        return (bool)\preg_match('/:(?:' . self::OF_TYPE_PSEUDO_CLASS_MATCHER . ')/i', $selectorPart);
    }

    /**
     * @param string[] $a
     * @param string[] $b
     *
     * @return int
     */
    private function sortBySelectorPrecedence(array $a, array $b): int
    {
        $precedenceA = $this->getCssSelectorPrecedence($a['selector']);
        $precedenceB = $this->getCssSelectorPrecedence($b['selector']);

        // We want these sorted in ascending order so selectors with lesser precedence get processed first and
        // selectors with greater precedence get sorted last.
        $precedenceForEquals = ($a['line'] < $b['line'] ? -1 : 1);
        $precedenceForNotEquals = ($precedenceA < $precedenceB ? -1 : 1);
        return ($precedenceA === $precedenceB) ? $precedenceForEquals : $precedenceForNotEquals;
    }

    /**
     * @param string $selector
     *
     * @return int
     */
    private function getCssSelectorPrecedence(string $selector): int
    {
        $selectorKey = \md5($selector);
        if (isset($this->caches[self::CACHE_KEY_SELECTOR][$selectorKey])) {
            return $this->caches[self::CACHE_KEY_SELECTOR][$selectorKey];
        }

        $precedence = 0;
        foreach ($this->selectorPrecedenceMatchers as $matcher => $value) {
            if (\trim($selector) === '') {
                break;
            }
            $number = 0;
            $selector = \preg_replace('/' . $matcher . '\\w+/', '', $selector, -1, $number);
            $precedence += ($value * (int)$number);
        }
        $this->caches[self::CACHE_KEY_SELECTOR][$selectorKey] = $precedence;

        return $precedence;
    }

    /**
     * Collates the media query, selectors and declarations for individual rules from a `CssDocument` containing parsed
     * CSS, in order.
     *
     * @param CssDocument $cssDocument
     *
     * @return array<array-key, array<string, string>> Array of string sub-arrays with the keys
     *         "media" (the media query string, e.g. "@media screen and (max-width: 480px)",
     *         or an empty string if not from an `@media` rule),
     *         "selectors" (the CSS selector(s), e.g., "*" or "h1, h2"),
     *         "declarations" (the semicolon-separated CSS declarations for that/those selector(s),
     *         e.g., "color: red; height: 4px;"),
     */
    private function getCssRuleMatches(CssDocument $cssDocument): array
    {
        $ruleMatches = [];
        /** @psalm-var CssRenderable $rule */
        foreach ($cssDocument->getContents() as $rule) {
            if ($rule instanceof CssAtRuleBlockList && $rule->atRuleName() === 'media') {
                /** @psalm-var string $mediaQueryList */
                $mediaQueryList = $rule->atRuleArgs();
                [$mediaType] = \explode('(', $mediaQueryList, 2);
                if (\trim($mediaType) !== '') {
                    $mediaTypesExpression = \implode('|', \array_keys($this->allowedMediaTypes));
                    if (!\preg_match('/^\\s*+(?:only\\s++)?+(?:' . $mediaTypesExpression . ')/i', $mediaType)) {
                        continue;
                    }
                }
                $media = '@media ' . $mediaQueryList;
                /** @psalm-var CssRenderable $nestedRule */
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
     * Copies $cssRule into the style attribute of $node.
     *
     * Note: This method does not check whether $cssRule matches $node.
     *
     * @param \DOMElement $node
     * @param string[][] $cssRule
     */
    private function copyInlinableCssToStyleAttribute(\DOMElement $node, array $cssRule): void
    {
        /** @var string $declarationsBlock */
        $declarationsBlock = $cssRule['declarationsBlock'];
        $newStyleDeclarations = $this->parseCssDeclarationsBlock($declarationsBlock);
        if ($newStyleDeclarations === []) {
            return;
        }

        // if it has a style attribute, get it, process it, and append (overwrite) new stuff
        if ($node->hasAttribute('style')) {
            // break it up into an associative array
            $oldStyleDeclarations = $this->parseCssDeclarationsBlock($node->getAttribute('style'));
        } else {
            $oldStyleDeclarations = [];
        }
        $node->setAttribute(
            'style',
            $this->generateStyleStringFromDeclarationsArrays($oldStyleDeclarations, $newStyleDeclarations)
        );
    }

    /**
     * This method merges old or existing name/value array with new name/value array
     * and then generates a string of the combined style suitable for placing inline.
     * This becomes the single point for CSS string generation allowing for consistent
     * CSS output no matter where the CSS originally came from.
     *
     * @param string[] $oldStyles
     * @param string[] $newStyles
     *
     * @return string
     */
    private function generateStyleStringFromDeclarationsArrays(array $oldStyles, array $newStyles): string
    {
        $cacheKey = \serialize([$oldStyles, $newStyles]);
        if (isset($this->caches[self::CACHE_KEY_COMBINED_STYLES][$cacheKey])) {
            return $this->caches[self::CACHE_KEY_COMBINED_STYLES][$cacheKey];
        }

        // Unset the overridden styles to preserve order, important if shorthand and individual properties are mixed
        foreach ($oldStyles as $attributeName => $attributeValue) {
            if (!isset($newStyles[$attributeName])) {
                continue;
            }

            $newAttributeValue = $newStyles[$attributeName];
            if (
                $this->attributeValueIsImportant($attributeValue)
                && !$this->attributeValueIsImportant($newAttributeValue)
            ) {
                unset($newStyles[$attributeName]);
            } else {
                unset($oldStyles[$attributeName]);
            }
        }

        $combinedStyles = \array_merge($oldStyles, $newStyles);

        $style = '';
        foreach ($combinedStyles as $attributeName => $attributeValue) {
            $style .= \strtolower(\trim($attributeName)) . ': ' . \trim($attributeValue) . '; ';
        }
        $trimmedStyle = \rtrim($style);

        $this->caches[self::CACHE_KEY_COMBINED_STYLES][$cacheKey] = $trimmedStyle;

        return $trimmedStyle;
    }

    /**
     * Checks whether $attributeValue is marked as !important.
     *
     * @param string $attributeValue
     *
     * @return bool
     */
    private function attributeValueIsImportant(string $attributeValue): bool
    {
        return (bool)\preg_match('/!\\s*+important$/i', $attributeValue);
    }

    /**
     * Merges styles from styles attributes and style nodes and applies them to the attribute nodes
     */
    private function fillStyleAttributesWithMergedStyles(): void
    {
        foreach ($this->styleAttributesForNodes as $nodePath => $styleAttributesForNode) {
            $node = $this->visitedNodes[$nodePath];
            $currentStyleAttributes = $this->parseCssDeclarationsBlock($node->getAttribute('style'));
            $node->setAttribute(
                'style',
                $this->generateStyleStringFromDeclarationsArrays(
                    $currentStyleAttributes,
                    $styleAttributesForNode
                )
            );
        }
    }

    /**
     * Searches for all nodes with a style attribute and removes the "!important" annotations out of
     * the inline style declarations, eventually by rearranging declarations.
     *
     * @throws \RuntimeException
     */
    private function removeImportantAnnotationFromAllInlineStyles(): void
    {
        foreach ($this->getAllNodesWithStyleAttribute() as $node) {
            $this->removeImportantAnnotationFromNodeInlineStyle($node);
        }
    }

    /**
     * Removes the "!important" annotations out of the inline style declarations,
     * eventually by rearranging declarations.
     * Rearranging needed when !important shorthand properties are followed by some of their
     * not !important expanded-version properties.
     * For example "font: 12px serif !important; font-size: 13px;" must be reordered
     * to "font-size: 13px; font: 12px serif;" in order to remain correct.
     *
     * @param \DOMElement $node
     *
     * @throws \RuntimeException
     */
    private function removeImportantAnnotationFromNodeInlineStyle(\DOMElement $node): void
    {
        $inlineStyleDeclarations = $this->parseCssDeclarationsBlock($node->getAttribute('style'));
        $regularStyleDeclarations = [];
        $importantStyleDeclarations = [];
        foreach ($inlineStyleDeclarations as $property => $value) {
            if ($this->attributeValueIsImportant($value)) {
                $importantStyleDeclarations[$property] = $this->pregReplace('/\\s*+!\\s*+important$/i', '', $value);
            } else {
                $regularStyleDeclarations[$property] = $value;
            }
        }
        $inlineStyleDeclarationsInNewOrder = \array_merge(
            $regularStyleDeclarations,
            $importantStyleDeclarations
        );
        $node->setAttribute(
            'style',
            $this->generateStyleStringFromSingleDeclarationsArray($inlineStyleDeclarationsInNewOrder)
        );
    }

    /**
     * Generates a CSS style string suitable to be used inline from the $styleDeclarations property => value array.
     *
     * @param string[] $styleDeclarations
     *
     * @return string
     */
    private function generateStyleStringFromSingleDeclarationsArray(array $styleDeclarations): string
    {
        return $this->generateStyleStringFromDeclarationsArrays([], $styleDeclarations);
    }

    /**
     * Determines which of `$cssRules` actually apply to `$this->domDocument`, and sets them in
     * `$this->matchingUninlinableCssRules`.
     *
     * @param (string|bool|int)[][] $cssRules the "uninlinable" array of CSS rules returned by `collateCssRules`
     */
    private function determineMatchingUninlinableCssRules(array $cssRules): void
    {
        $this->matchingUninlinableCssRules = \array_filter($cssRules, [$this, 'existsMatchForSelectorInCssRule']);
    }

    /**
     * Checks whether there is at least one matching element for the CSS selector contained in the `selector` element
     * of the provided CSS rule.
     *
     * Any dynamic pseudo-classes will be assumed to apply. If the selector matches a pseudo-element,
     * it will test for a match with its originating element.
     *
     * @param (string|bool|int)[] $cssRule
     *
     * @return bool
     *
     * @throws ParseException
     */
    private function existsMatchForSelectorInCssRule(array $cssRule): bool
    {
        /** @psalm-var string $selector */
        $selector = $cssRule['selector'];
        if ($cssRule['hasUnmatchablePseudo']) {
            $selector = $this->removeUnmatchablePseudoComponents($selector);
        }
        return $this->existsMatchForCssSelector($selector);
    }

    /**
     * Checks whether there is at least one matching element for $cssSelector.
     * When not in debug mode, it returns true also for invalid selectors (because they may be valid,
     * just not implemented/recognized yet by Emogrifier).
     *
     * @param string $cssSelector
     *
     * @return bool
     *
     * @throws ParseException
     */
    private function existsMatchForCssSelector(string $cssSelector): bool
    {
        try {
            $nodesMatchingSelector = $this->xPath->query($this->getCssSelectorConverter()->toXPath($cssSelector));
        } catch (ParseException $e) {
            if ($this->debug) {
                throw $e;
            }
            return true;
        }

        return $nodesMatchingSelector !== false && $nodesMatchingSelector->length !== 0;
    }

    /**
     * Removes pseudo-elements and dynamic pseudo-classes from a CSS selector, replacing them with "*" if necessary.
     * If such a pseudo-component is within the argument of `:not`, the entire `:not` component is removed or replaced.
     *
     * @param string $selector
     *
     * @return string Selector which will match the relevant DOM elements if the pseudo-classes are assumed to apply,
     *                or in the case of pseudo-elements will match their originating element.
     */
    private function removeUnmatchablePseudoComponents(string $selector): string
    {
        // The regex allows nested brackets via `(?2)`.
        // A space is temporarily prepended because the callback can't determine if the match was at the very start.
        $selectorWithoutNots = \ltrim(\preg_replace_callback(
            '/([\\s>+~]?+):not(\\([^()]*+(?:(?2)[^()]*+)*+\\))/i',
            [$this, 'replaceUnmatchableNotComponent'],
            ' ' . $selector
        ));

        $selectorWithoutUnmatchablePseudoComponents = $this->removeSelectorComponents(
            ':(?!' . self::PSEUDO_CLASS_MATCHER . '):?+[\\w\\-]++(?:\\([^\\)]*+\\))?+',
            $selectorWithoutNots
        );

        if (
            !\preg_match(
                '/:(?:' . self::OF_TYPE_PSEUDO_CLASS_MATCHER . ')/i',
                $selectorWithoutUnmatchablePseudoComponents
            )
        ) {
            return $selectorWithoutUnmatchablePseudoComponents;
        }
        return \implode('', \array_map(
            [$this, 'removeUnsupportedOfTypePseudoClasses'],
            \preg_split(
                '/(' . self::COMBINATOR_MATCHER . ')/',
                $selectorWithoutUnmatchablePseudoComponents,
                -1,
                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
            )
        ));
    }

    /**
     * Helps `removeUnmatchablePseudoComponents()` replace or remove a selector `:not(...)` component if its argument
     * contains pseudo-elements or dynamic pseudo-classes.
     *
     * @param string[] $matches array of elements matched by the regular expression
     *
     * @return string the full match if there were no unmatchable pseudo components within; otherwise, any preceding
     *         combinator followed by "*", or an empty string if there was no preceding combinator
     */
    private function replaceUnmatchableNotComponent(array $matches): string
    {
        [$notComponentWithAnyPrecedingCombinator, $anyPrecedingCombinator, $notArgumentInBrackets] = $matches;

        if ($this->hasUnsupportedPseudoClass($notArgumentInBrackets)) {
            return $anyPrecedingCombinator !== '' ? $anyPrecedingCombinator . '*' : '';
        }
        return $notComponentWithAnyPrecedingCombinator;
    }

    /**
     * Removes components from a CSS selector, replacing them with "*" if necessary.
     *
     * @param string $matcher regular expression part to match the components to remove
     * @param string $selector
     *
     * @return string selector which will match the relevant DOM elements if the removed components are assumed to apply
     *         (or in the case of pseudo-elements will match their originating element)
     */
    private function removeSelectorComponents(string $matcher, string $selector): string
    {
        return \preg_replace(
            ['/([\\s>+~]|^)' . $matcher . '/i', '/' . $matcher . '/i'],
            ['$1*', ''],
            $selector
        );
    }

    /**
     * Removes any `...-of-type` pseudo-classes from part of a CSS selector, if it does not have a type, replacing them
     * with "*" if necessary.
     *
     * @param string $selectorPart part of a selector which has been split up at combinators
     *
     * @return string selector part which will match the relevant DOM elements if the pseudo-classes are assumed to
     *         apply
     */
    private function removeUnsupportedOfTypePseudoClasses(string $selectorPart): string
    {
        if (!$this->selectorPartHasUnsupportedOfTypePseudoClass($selectorPart)) {
            return $selectorPart;
        }

        return $this->removeSelectorComponents(
            ':(?:' . self::OF_TYPE_PSEUDO_CLASS_MATCHER . ')(?:\\([^\\)]*+\\))?+',
            $selectorPart
        );
    }

    /**
     * Applies `$this->matchingUninlinableCssRules` to `$this->domDocument` by placing them as CSS in a `<style>`
     * element.
     *
     * @param CssDocument $cssDocument This may contain any `@import`, `@font-face`, or other at-rules that should
     *        precede the CSS placed in the `<style>` element.  If there are no unlinlinable CSS rules to copy there, a
     *        `<style>` element will be created containing only the applicable at-rules from `$cssDocument`.  If there
     *        are none, and there are also no unlinlinable CSS rules, an empty `<style>` element will not be created.
     */
    private function copyUninlinableCssToStyleNode(CssDocument $cssDocument): void
    {
        $css = $this->renderUnprocessableAtRules($cssDocument);

        // avoid including unneeded class dependency if there are no rules
        if ($this->matchingUninlinableCssRules !== []) {
            $cssConcatenator = new CssConcatenator();
            foreach ($this->matchingUninlinableCssRules as $cssRule) {
                // This is a fucking load of crap to satisfy both Psalm and PHPMD.
                // These tools are just encouraging bad and sub-optimal programming, not good programming.
                // They do not help at all.
                /** @psalm-var string $selector */
                $selector = $cssRule['selector'];
                /** @psalm-var string $declarationsBlock */
                $declarationsBlock = $cssRule['declarationsBlock'];
                /** @psalm-var string $media */
                $media = $cssRule['media'];
                $cssConcatenator->append([$selector], $declarationsBlock, $media);
            }
            $css .= $cssConcatenator->getCss();
        }

        // avoid adding empty style element
        if ($css !== '') {
            $this->addStyleElementToDocument($css);
        }
    }

    /**
     * Renders at-rules from the supplied parsed CSS that are valid and have no special alternative processing.
     *
     * @param CssDocument $cssDocument
     *
     * @return string
     */
    private function renderUnprocessableAtRules(CssDocument $cssDocument): string
    {
        $this->isImportRuleAllowed = true;
        /** @psalm-var CssRenderable[] $cssContents */
        $cssContents = $cssDocument->getContents();
        $atRules = \array_filter($cssContents, [$this, 'isValidAtRuleToPassThrough']);

        if ($atRules === []) {
            return '';
        }

        $atRulesDocument = new CssDocument();
        $atRulesDocument->setContents($atRules);

        /** @psalm-var string $renderedRules */
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
    private function isValidAtRuleToPassThrough(CssRenderable $rule): bool
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

    /**
     * Adds a style element with $css to $this->domDocument.
     *
     * This method is protected to allow overriding.
     *
     * @see https://github.com/MyIntervals/emogrifier/issues/103
     *
     * @param string $css
     */
    protected function addStyleElementToDocument(string $css): void
    {
        $styleElement = $this->domDocument->createElement('style', $css);
        $styleAttribute = $this->domDocument->createAttribute('type');
        $styleAttribute->value = 'text/css';
        $styleElement->appendChild($styleAttribute);

        $headElement = $this->getHeadElement();
        $headElement->appendChild($styleElement);
    }

    /**
     * Returns the HEAD element.
     *
     * This method assumes that there always is a HEAD element.
     *
     * @return \DOMElement
     */
    private function getHeadElement(): \DOMElement
    {
        return $this->domDocument->getElementsByTagName('head')->item(0);
    }

    /**
     * Wraps `preg_replace`.  If an error occurs (which is highly unlikely), either it is logged and the original
     * `$subject` is returned, or in debug mode an exception is thrown.
     *
     * This method does not currently allow `$subject` (and return value) to be an array, because a means of telling
     * Psalm that a method returns the same type a particular parameter has not been found (though it knows this for
     * `preg_replace`); nor does it currently support the optional parameters.
     *
     * @param string|string[] $pattern
     * @param string|string[] $replacement
     * @param string $subject
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    private function pregReplace($pattern, $replacement, string $subject): string
    {
        $result = \preg_replace($pattern, $replacement, $subject);

        if ($result === null) {
            $this->logOrThrowPregLastError();
            $result = $subject;
        }

        return $result;
    }

    /**
     * Obtains the name of the error constant for `preg_last_error` (based on code posted at
     * {@see https://www.php.net/manual/en/function.preg-last-error.php#124124}) and puts it into an error message
     * which is either passed to `trigger_error` (in non-debug mode) or an exception which is thrown (in debug mode).
     *
     * @throws \RuntimeException
     */
    private function logOrThrowPregLastError(): void
    {
        $pcreConstants = \get_defined_constants(true)['pcre'];
        $pcreErrorConstantNames = \is_array($pcreConstants) ? \array_flip(\array_filter(
            $pcreConstants,
            function (string $key): bool {
                return \substr($key, -6) === '_ERROR';
            },
            ARRAY_FILTER_USE_KEY
        )) : [];

        $pregLastError = \preg_last_error();
        $message = 'PCRE regex execution error `' . (string)($pcreErrorConstantNames[$pregLastError] ?? $pregLastError)
            . '`';

        if ($this->debug) {
            throw new \RuntimeException($message, 1592870147);
        }
        \trigger_error($message);
    }
}
