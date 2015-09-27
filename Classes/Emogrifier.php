<?php
namespace Pelago;

/**
 * This class provides functions for converting CSS styles into inline style attributes in your HTML code.
 *
 * For more information, please see the README.md file.
 *
 * @version 0.1.1
 *
 * @author Cameron Brooks
 * @author Jaime Prado
 * @author Roman Ožana <ozana@omdesign.cz>
 */
class Emogrifier
{
    /**
     * @var int
     */
    const CACHE_KEY_CSS = 0;

    /**
     * @var int
     */
    const CACHE_KEY_SELECTOR = 1;

    /**
     * @var int
     */
    const CACHE_KEY_XPATH = 2;

    /**
     * @var int
     */
    const CACHE_KEY_CSS_DECLARATION_BLOCK = 3;

    /**
     * @var int
     */
    const CACHE_KEY_COMBINED_STYLES = 4;

    /**
     * for calculating nth-of-type and nth-child selectors
     *
     * @var int
     */
    const INDEX = 0;

    /**
     * for calculating nth-of-type and nth-child selectors
     *
     * @var int
     */
    const MULTIPLIER = 1;

    /**
     * @var string
     */
    const ID_ATTRIBUTE_MATCHER = '/(\\w+)?\\#([\\w\\-]+)/';

    /**
     * @var string
     */
    const CLASS_ATTRIBUTE_MATCHER = '/(\\w+|[\\*\\]])?((\\.[\\w\\-]+)+)/';

    /**
     * @var string
     */
    const CONTENT_TYPE_META_TAG = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';

    /**
     * @var string
     */
    private $html = '';

    /**
     * @var string
     */
    private $css = '';

    /**
     * @var bool[]
     */
    private $excludedSelectors = [];

    /**
     * @var string[]
     */
    private $unprocessableHtmlTags = ['wbr'];

    /**
     * @var bool[]
     */
    private $allowedMediaTypes = ['all' => true, 'screen' => true, 'print' => true];

    /**
     * @var array[]
     */
    private $caches = [
        self::CACHE_KEY_CSS => [],
        self::CACHE_KEY_SELECTOR => [],
        self::CACHE_KEY_XPATH => [],
        self::CACHE_KEY_CSS_DECLARATION_BLOCK => [],
        self::CACHE_KEY_COMBINED_STYLES => [],
    ];

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
     * @var array[]
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
     * Determines whether elements with the `display: none` property are
     * removed from the DOM.
     *
     * @var bool
     */
    private $shouldKeepInvisibleNodes = true;

    /**
     * The constructor.
     *
     * @param string $html the HTML to emogrify, must be UTF-8-encoded
     * @param string $css the CSS to merge, must be UTF-8-encoded
     */
    public function __construct($html = '', $css = '')
    {
        $this->setHtml($html);
        $this->setCss($css);
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->purgeVisitedNodes();
    }

    /**
     * Sets the HTML to emogrify.
     *
     * @param string $html the HTML to emogrify, must be UTF-8-encoded
     *
     * @return void
     */
    public function setHtml($html)
    {
        $this->html = $html;
    }

    /**
     * Sets the CSS to merge with the HTML.
     *
     * @param string $css the CSS to merge, must be UTF-8-encoded
     *
     * @return void
     */
    public function setCss($css)
    {
        $this->css = $css;
    }

    /**
     * Applies $this->css to $this->html and returns the HTML with the CSS
     * applied.
     *
     * This method places the CSS inline.
     *
     * @return string
     *
     * @throws \BadMethodCallException
     */
    public function emogrify()
    {
        if ($this->html === '') {
            throw new \BadMethodCallException('Please set some HTML first before calling emogrify.', 1390393096);
        }

        $xmlDocument = $this->createXmlDocument();
        $this->process($xmlDocument);

        return $xmlDocument->saveHTML();
    }

    /**
     * Applies $this->css to $this->html and returns only the HTML content
     * within the <body> tag.
     *
     * This method places the CSS inline.
     *
     * @return string
     *
     * @throws \BadMethodCallException
     */
    public function emogrifyBodyContent()
    {
        if ($this->html === '') {
            throw new \BadMethodCallException('Please set some HTML first before calling emogrify.', 1390393096);
        }

        $xmlDocument = $this->createXmlDocument();
        $this->process($xmlDocument);

        $innerDocument = new \DOMDocument();
        foreach ($xmlDocument->documentElement->getElementsByTagName('body')->item(0)->childNodes as $childNode) {
            $innerDocument->appendChild($innerDocument->importNode($childNode, true));
        }

        return $innerDocument->saveHTML();
    }

    /**
     * Applies $this->css to $xmlDocument.
     *
     * This method places the CSS inline.
     *
     * @param \DOMDocument $xmlDocument
     *
     * @return void
     */
    protected function process(\DOMDocument $xmlDocument)
    {
        $xpath = new \DOMXPath($xmlDocument);
        $this->clearAllCaches();

        // Before be begin processing the CSS file, parse the document and normalize all existing CSS attributes.
        // This changes 'DISPLAY: none' to 'display: none'.
        // We wouldn't have to do this if DOMXPath supported XPath 2.0.
        // Also store a reference of nodes with existing inline styles so we don't overwrite them.
        $this->purgeVisitedNodes();

        $nodesWithStyleAttributes = $xpath->query('//*[@style]');
        if ($nodesWithStyleAttributes !== false) {
            /** @var \DOMElement $node */
            foreach ($nodesWithStyleAttributes as $node) {
                if ($this->isInlineStyleAttributesParsingEnabled) {
                    $this->normalizeStyleAttributes($node);
                } else {
                    $node->removeAttribute('style');
                }
            }
        }

        // grab any existing style blocks from the html and append them to the existing CSS
        // (these blocks should be appended so as to have precedence over conflicting styles in the existing CSS)
        $allCss = $this->css;

        if ($this->isStyleBlocksParsingEnabled) {
            $allCss .= $this->getCssFromAllStyleNodes($xpath);
        }

        $cssParts = $this->splitCssAndMediaQuery($allCss);
        $excludedNodes = $this->getNodesToExclude($xpath);
        foreach ($this->parseSelectors($cssParts['css']) as $selector) {
            // query the body for the xpath selector
            $nodesMatchingCssSelectors = $xpath->query($this->translateCssToXpath($selector['selector']));
            // ignore invalid selectors
            if ($nodesMatchingCssSelectors === false) {
                continue;
            }

            /** @var \DOMElement $node */
            foreach ($nodesMatchingCssSelectors as $node) {
                if (in_array($node, $excludedNodes, true)) {
                    continue;
                }

                // if it has a style attribute, get it, process it, and append (overwrite) new stuff
                if ($node->hasAttribute('style')) {
                    // break it up into an associative array
                    $oldStyleDeclarations = $this->parseCssDeclarationBlock($node->getAttribute('style'));
                } else {
                    $oldStyleDeclarations = [];
                }
                $newStyleDeclarations = $this->parseCssDeclarationBlock($selector['attributes']);
                $node->setAttribute(
                    'style',
                    $this->generateStyleStringFromDeclarationsArrays($oldStyleDeclarations, $newStyleDeclarations)
                );
            }
        }

        if ($this->isInlineStyleAttributesParsingEnabled) {
            $this->fillStyleAttributesWithMergedStyles();
        }

        if ($this->shouldKeepInvisibleNodes) {
            $this->removeInvisibleNodes($xpath);
        }

        $this->copyCssWithMediaToStyleNode($xmlDocument, $xpath, $cssParts['media']);
    }

    /**
     * Parses a list of selectors from a string of CSS.
     *
     * @param string $css a string of raw CSS code
     *
     * @return string[][] an array of string sub-arrays with the keys "selector", "attributes", and "line"
     */
    private function parseSelectors($css)
    {
        $cssKey = md5($css);
        if (!isset($this->caches[self::CACHE_KEY_CSS][$cssKey])) {
            // process the CSS file for selectors and definitions
            preg_match_all('/(?:^|[\\s^{}]*)([^{]+){([^}]*)}/mis', $css, $matches, PREG_SET_ORDER);

            $allSelectors = [];
            foreach ($matches as $key => $selectorString) {
                // if there is a blank definition, skip
                if (!strlen(trim($selectorString[2]))) {
                    continue;
                }

                // else split by commas and duplicate attributes so we can sort by selector precedence
                $selectors = explode(',', $selectorString[1]);
                foreach ($selectors as $selector) {
                    // don't process pseudo-elements and behavioral (dynamic) pseudo-classes;
                    // only allow structural pseudo-classes
                    if (strpos($selector, ':') !== false && !preg_match('/:\\S+\\-(child|type)\\(/i', $selector)) {
                        continue;
                    }

                    $allSelectors[] = [
                        'selector' => trim($selector),
                        'attributes' => trim($selectorString[2]),
                        // keep track of where it appears in the file, since order is important
                        'line' => $key,
                    ];
                }
            }

            usort($allSelectors, [$this, 'sortBySelectorPrecedence']);

            $this->caches[self::CACHE_KEY_CSS][$cssKey] = $allSelectors;
        }

        return $this->caches[self::CACHE_KEY_CSS][$cssKey];
    }

    /**
     * Disables the parsing of inline styles.
     *
     * @return void
     */
    public function disableInlineStyleAttributesParsing()
    {
        $this->isInlineStyleAttributesParsingEnabled = false;
    }

    /**
     * Disables the parsing of <style> blocks.
     *
     * @return void
     */
    public function disableStyleBlocksParsing()
    {
        $this->isStyleBlocksParsingEnabled = false;
    }

    /**
     * Disables the removal of elements with `display: none` properties.
     *
     * @return void
     */
    public function disableInvisibleNodeRemoval()
    {
        $this->shouldKeepInvisibleNodes = false;
    }

    /**
     * Clears all caches.
     *
     * @return void
     */
    private function clearAllCaches()
    {
        $this->clearCache(self::CACHE_KEY_CSS);
        $this->clearCache(self::CACHE_KEY_SELECTOR);
        $this->clearCache(self::CACHE_KEY_XPATH);
        $this->clearCache(self::CACHE_KEY_CSS_DECLARATION_BLOCK);
        $this->clearCache(self::CACHE_KEY_COMBINED_STYLES);
    }

    /**
     * Clears a single cache by key.
     *
     * @param int $key the cache key, must be CACHE_KEY_CSS, CACHE_KEY_SELECTOR, CACHE_KEY_XPATH
     *                 or CACHE_KEY_CSS_DECLARATION_BLOCK
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function clearCache($key)
    {
        $allowedCacheKeys = [
            self::CACHE_KEY_CSS,
            self::CACHE_KEY_SELECTOR,
            self::CACHE_KEY_XPATH,
            self::CACHE_KEY_CSS_DECLARATION_BLOCK,
            self::CACHE_KEY_COMBINED_STYLES,
        ];
        if (!in_array($key, $allowedCacheKeys, true)) {
            throw new \InvalidArgumentException('Invalid cache key: ' . $key, 1391822035);
        }

        $this->caches[$key] = [];
    }

    /**
     * Purges the visited nodes.
     *
     * @return void
     */
    private function purgeVisitedNodes()
    {
        $this->visitedNodes = [];
        $this->styleAttributesForNodes = [];
    }

    /**
     * Marks a tag for removal.
     *
     * There are some HTML tags that DOMDocument cannot process, and it will throw an error if it encounters them.
     * In particular, DOMDocument will complain if you try to use HTML5 tags in an XHTML document.
     *
     * Note: The tags will not be removed if they have any content.
     *
     * @param string $tagName the tag name, e.g., "p"
     *
     * @return void
     */
    public function addUnprocessableHtmlTag($tagName)
    {
        $this->unprocessableHtmlTags[] = $tagName;
    }

    /**
     * Drops a tag from the removal list.
     *
     * @param string $tagName the tag name, e.g., "p"
     *
     * @return void
     */
    public function removeUnprocessableHtmlTag($tagName)
    {
        $key = array_search($tagName, $this->unprocessableHtmlTags, true);
        if ($key !== false) {
            unset($this->unprocessableHtmlTags[$key]);
        }
    }

    /**
     * Marks a media query type to keep.
     *
     * @param string $mediaName the media type name, e.g., "braille"
     *
     * @return void
     */
    public function addAllowedMediaType($mediaName)
    {
        $this->allowedMediaTypes[$mediaName] = true;
    }

    /**
     * Drops a media query type from the allowed list.
     *
     * @param string $mediaName the tag name, e.g., "braille"
     *
     * @return void
     */
    public function removeAllowedMediaType($mediaName)
    {
        if (isset($this->allowedMediaTypes[$mediaName])) {
            unset($this->allowedMediaTypes[$mediaName]);
        }
    }

    /**
     * Adds a selector to exclude nodes from emogrification.
     *
     * Any nodes that match the selector will not have their style altered.
     *
     * @param string $selector the selector to exclude, e.g., ".editor"
     *
     * @return void
     */
    public function addExcludedSelector($selector)
    {
        $this->excludedSelectors[$selector] = true;
    }

    /**
     * No longer excludes the nodes matching this selector from emogrification.
     *
     * @param string $selector the selector to no longer exclude, e.g., ".editor"
     *
     * @return void
     */
    public function removeExcludedSelector($selector)
    {
        if (isset($this->excludedSelectors[$selector])) {
            unset($this->excludedSelectors[$selector]);
        }
    }

    /**
     * This removes styles from your email that contain display:none.
     * We need to look for display:none, but we need to do a case-insensitive search. Since DOMDocument only
     * supports XPath 1.0, lower-case() isn't available to us. We've thus far only set attributes to lowercase,
     * not attribute values. Consequently, we need to translate() the letters that would be in 'NONE' ("NOE")
     * to lowercase.
     *
     * @param \DOMXPath $xpath
     *
     * @return void
     */
    private function removeInvisibleNodes(\DOMXPath $xpath)
    {
        $nodesWithStyleDisplayNone = $xpath->query(
            '//*[contains(translate(translate(@style," ",""),"NOE","noe"),"display:none")]'
        );
        if ($nodesWithStyleDisplayNone->length === 0) {
            return;
        }

        // The checks on parentNode and is_callable below ensure that if we've deleted the parent node,
        // we don't try to call removeChild on a nonexistent child node
        /** @var \DOMNode $node */
        foreach ($nodesWithStyleDisplayNone as $node) {
            if ($node->parentNode && is_callable([$node->parentNode, 'removeChild'])) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    /**
     * Normalizes the value of the "style" attribute and saves it.
     *
     * @param \DOMElement $node
     *
     * @return void
     */
    private function normalizeStyleAttributes(\DOMElement $node)
    {
        $normalizedOriginalStyle = preg_replace_callback(
            '/[A-z\\-]+(?=\\:)/S',
            function (array $m) {
                return strtolower($m[0]);
            },
            $node->getAttribute('style')
        );

        // in order to not overwrite existing style attributes in the HTML, we
        // have to save the original HTML styles
        $nodePath = $node->getNodePath();
        if (!isset($this->styleAttributesForNodes[$nodePath])) {
            $this->styleAttributesForNodes[$nodePath] = $this->parseCssDeclarationBlock($normalizedOriginalStyle);
            $this->visitedNodes[$nodePath] = $node;
        }

        $node->setAttribute('style', $normalizedOriginalStyle);
    }

    /**
     * Merges styles from styles attributes and style nodes and applies them to the attribute nodes
     *
     * @return void
     */
    private function fillStyleAttributesWithMergedStyles()
    {
        foreach ($this->styleAttributesForNodes as $nodePath => $styleAttributesForNode) {
            $node = $this->visitedNodes[$nodePath];
            $currentStyleAttributes = $this->parseCssDeclarationBlock($node->getAttribute('style'));
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
    private function generateStyleStringFromDeclarationsArrays(array $oldStyles, array $newStyles)
    {
        $combinedStyles = array_merge($oldStyles, $newStyles);
        $cacheKey = serialize($combinedStyles);
        if (isset($this->caches[self::CACHE_KEY_COMBINED_STYLES][$cacheKey])) {
            return $this->caches[self::CACHE_KEY_COMBINED_STYLES][$cacheKey];
        }

        foreach ($oldStyles as $attributeName => $attributeValue) {
            if (isset($newStyles[$attributeName]) && strtolower(substr($attributeValue, -10)) === '!important') {
                $combinedStyles[$attributeName] = $attributeValue;
            }
        }

        $style = '';
        foreach ($combinedStyles as $attributeName => $attributeValue) {
            $style .= strtolower(trim($attributeName)) . ': ' . trim($attributeValue) . '; ';
        }
        $trimmedStyle = rtrim($style);

        $this->caches[self::CACHE_KEY_COMBINED_STYLES][$cacheKey] = $trimmedStyle;

        return $trimmedStyle;
    }

    /**
     * Applies $css to $xmlDocument, limited to the media queries that actually apply to the document.
     *
     * @param \DOMDocument $xmlDocument the document to match against
     * @param \DOMXPath $xpath
     * @param string $css a string of CSS
     *
     * @return void
     */
    private function copyCssWithMediaToStyleNode(\DOMDocument $xmlDocument, \DOMXPath $xpath, $css)
    {
        if ($css === '') {
            return;
        }

        $mediaQueriesRelevantForDocument = [];

        foreach ($this->extractMediaQueriesFromCss($css) as $mediaQuery) {
            foreach ($this->parseSelectors($mediaQuery['css']) as $selector) {
                if ($this->existsMatchForCssSelector($xpath, $selector['selector'])) {
                    $mediaQueriesRelevantForDocument[] = $mediaQuery['query'];
                    break;
                }
            }
        }

        $this->addStyleElementToDocument($xmlDocument, implode($mediaQueriesRelevantForDocument));
    }

    /**
     * Extracts the media queries from $css.
     *
     * @param string $css
     *
     * @return string[][] numeric array with string sub-arrays with the keys "css" and "query"
     */
    private function extractMediaQueriesFromCss($css)
    {
        preg_match_all('#(?<query>@media[^{]*\\{(?<css>(.*?)\\})(\\s*)\\})#s', $css, $mediaQueries);
        $result = [];
        foreach (array_keys($mediaQueries['css']) as $key) {
            $result[] = [
                'css' => $mediaQueries['css'][$key],
                'query' => $mediaQueries['query'][$key],
            ];
        }
        return $result;
    }

    /**
     * Checks whether there is at least one matching element for $cssSelector.
     *
     * @param \DOMXPath $xpath
     * @param string $cssSelector
     *
     * @return bool
     */
    private function existsMatchForCssSelector(\DOMXPath $xpath, $cssSelector)
    {
        $nodesMatchingSelector = $xpath->query($this->translateCssToXpath($cssSelector));

        return $nodesMatchingSelector !== false && $nodesMatchingSelector->length !== 0;
    }

    /**
     * Returns CSS content.
     *
     * @param \DOMXPath $xpath
     *
     * @return string
     */
    private function getCssFromAllStyleNodes(\DOMXPath $xpath)
    {
        $styleNodes = $xpath->query('//style');

        if ($styleNodes === false) {
            return '';
        }

        $css = '';
        /** @var \DOMNode $styleNode */
        foreach ($styleNodes as $styleNode) {
            $css .= "\n\n" . $styleNode->nodeValue;
            $styleNode->parentNode->removeChild($styleNode);
        }

        return $css;
    }

    /**
     * Adds a style element with $css to $document.
     *
     * This method is protected to allow overriding.
     *
     * @see https://github.com/jjriv/emogrifier/issues/103
     *
     * @param \DOMDocument $document
     * @param string $css
     *
     * @return void
     */
    protected function addStyleElementToDocument(\DOMDocument $document, $css)
    {
        $styleElement = $document->createElement('style', $css);
        $styleAttribute = $document->createAttribute('type');
        $styleAttribute->value = 'text/css';
        $styleElement->appendChild($styleAttribute);

        $head = $this->getOrCreateHeadElement($document);
        $head->appendChild($styleElement);
    }

    /**
     * Returns the existing or creates a new head element in $document.
     *
     * @param \DOMDocument $document
     *
     * @return \DOMNode the head element
     */
    private function getOrCreateHeadElement(\DOMDocument $document)
    {
        $head = $document->getElementsByTagName('head')->item(0);

        if ($head === null) {
            $head = $document->createElement('head');
            $html = $document->getElementsByTagName('html')->item(0);
            $html->insertBefore($head, $document->getElementsByTagName('body')->item(0));
        }

        return $head;
    }

    /**
     * Splits input CSS code to an array where:
     *
     * - key "css" will be contains clean CSS code
     * - key "media" will be contains all valuable media queries
     *
     * Example:
     *
     * The CSS code
     *
     *   "@import "file.css"; h1 { color:red; } @media { h1 {}} @media tv { h1 {}}"
     *
     * will be parsed into the following array:
     *
     *   "css" => "h1 { color:red; }"
     *   "media" => "@media { h1 {}}"
     *
     * @param string $css
     *
     * @return string[]
     */
    private function splitCssAndMediaQuery($css)
    {
        $cssWithoutComments = preg_replace('/\\/\\*.*\\*\\//sU', '', $css);

        $mediaTypesExpression = '';
        if (!empty($this->allowedMediaTypes)) {
            $mediaTypesExpression = '|' . implode('|', array_keys($this->allowedMediaTypes));
        }

        $media = '';
        $cssForAllowedMediaTypes = preg_replace_callback(
            '#@media\\s+(?:only\\s)?(?:[\\s{\\(]' . $mediaTypesExpression . ')\\s?[^{]+{.*}\\s*}\\s*#misU',
            function ($matches) use (&$media) {
                $media .= $matches[0];
            },
            $cssWithoutComments
        );

        // filter the CSS
        $search = [
            'import directives' => '/^\\s*@import\\s[^;]+;/misU',
            'remaining media enclosures' => '/^\\s*@media\\s[^{]+{(.*)}\\s*}\\s/misU',
        ];

        $cleanedCss = preg_replace($search, '', $cssForAllowedMediaTypes);

        return ['css' => $cleanedCss, 'media' => $media];
    }

    /**
     * Creates a DOMDocument instance with the current HTML.
     *
     * @return \DOMDocument
     */
    private function createXmlDocument()
    {
        $xmlDocument = new \DOMDocument;
        $xmlDocument->encoding = 'UTF-8';
        $xmlDocument->strictErrorChecking = false;
        $xmlDocument->formatOutput = true;
        $libXmlState = libxml_use_internal_errors(true);
        $xmlDocument->loadHTML($this->getUnifiedHtml());
        libxml_clear_errors();
        libxml_use_internal_errors($libXmlState);
        $xmlDocument->normalizeDocument();

        return $xmlDocument;
    }

    /**
     * Returns the HTML with the unprocessable HTML tags removed and
     * with added Content-Type meta tag if needed.
     *
     * @return string the unified HTML
     *
     * @throws \BadMethodCallException
     */
    private function getUnifiedHtml()
    {
        $htmlWithoutUnprocessableTags = $this->removeUnprocessableTags($this->html);

        return $this->addContentTypeMetaTag($htmlWithoutUnprocessableTags);
    }

    /**
     * Removes the unprocessable tags from $html (if this feature is enabled).
     *
     * @param string $html
     *
     * @return string the reworked HTML with the unprocessable tags removed
     */
    private function removeUnprocessableTags($html)
    {
        if (empty($this->unprocessableHtmlTags)) {
            return $html;
        }

        $unprocessableHtmlTags = implode('|', $this->unprocessableHtmlTags);

        return preg_replace(
            '/<\\/?(' . $unprocessableHtmlTags . ')[^>]*>/i',
            '',
            $html
        );
    }

    /**
     * Adds a Content-Type meta tag for the charset.
     *
     * @param string $html
     *
     * @return string the HTML with the meta tag added
     */
    private function addContentTypeMetaTag($html)
    {
        $hasContentTypeMetaTag = stristr($html, 'Content-Type') !== false;
        if ($hasContentTypeMetaTag) {
            return $html;

        }

        // We are trying to insert the meta tag to the right spot in the DOM.
        // If we just prepended it to the HTML, we would lose attributes set to the HTML tag.
        $hasHeadTag = stripos($html, '<head') !== false;
        $hasHtmlTag = stripos($html, '<html') !== false;

        if ($hasHeadTag) {
            $reworkedHtml = preg_replace('/<head(.*?)>/i', '<head$1>' . self::CONTENT_TYPE_META_TAG, $html);
        } elseif ($hasHtmlTag) {
            $reworkedHtml = preg_replace(
                '/<html(.*?)>/i',
                '<html$1><head>' . self::CONTENT_TYPE_META_TAG . '</head>',
                $html
            );
        } else {
            $reworkedHtml = self::CONTENT_TYPE_META_TAG . $html;
        }

        return $reworkedHtml;
    }

    /**
     * @param string[] $a
     * @param string[] $b
     *
     * @return int
     */
    private function sortBySelectorPrecedence(array $a, array $b)
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
    private function getCssSelectorPrecedence($selector)
    {
        $selectorKey = md5($selector);
        if (!isset($this->caches[self::CACHE_KEY_SELECTOR][$selectorKey])) {
            $precedence = 0;
            $value = 100;
            // ids: worth 100, classes: worth 10, elements: worth 1
            $search = ['\\#','\\.',''];

            foreach ($search as $s) {
                if (trim($selector) === '') {
                    break;
                }
                $number = 0;
                $selector = preg_replace('/' . $s . '\\w+/', '', $selector, -1, $number);
                $precedence += ($value * $number);
                $value /= 10;
            }
            $this->caches[self::CACHE_KEY_SELECTOR][$selectorKey] = $precedence;
        }

        return $this->caches[self::CACHE_KEY_SELECTOR][$selectorKey];
    }

    /**
     * Right now, we support all CSS 1 selectors and most CSS2/3 selectors.
     *
     * @see http://plasmasturm.org/log/444/
     *
     * @param string $paramCssSelector
     *
     * @return string
     */
    private function translateCssToXpath($paramCssSelector)
    {
        $cssSelector = ' ' . $paramCssSelector . ' ';
        $cssSelector = preg_replace_callback(
            '/\\s+\\w+\\s+/',
            function (array $matches) {
                return strtolower($matches[0]);
            },
            $cssSelector
        );
        $cssSelector = trim($cssSelector);
        $xpathKey = md5($cssSelector);
        if (!isset($this->caches[self::CACHE_KEY_XPATH][$xpathKey])) {
            // returns an Xpath selector
            $search = [
                // Matches any element that is a child of parent.
                '/\\s+>\\s+/',
                // Matches any element that is an adjacent sibling.
                '/\\s+\\+\\s+/',
                // Matches any element that is a descendant of an parent element element.
                '/\\s+/',
                // first-child pseudo-selector
                '/([^\\/]+):first-child/i',
                // last-child pseudo-selector
                '/([^\\/]+):last-child/i',
                // Matches attribute only selector
                '/^\\[(\\w+|\\w+\\=[\'"]?\\w+[\'"]?)\\]/',
                // Matches element with attribute
                '/(\\w)\\[(\\w+)\\]/',
                // Matches element with EXACT attribute
                '/(\\w)\\[(\\w+)\\=[\'"]?(\\w+)[\'"]?\\]/',
            ];
            $replace = [
                '/',
                '/following-sibling::*[1]/self::',
                '//',
                '*[1]/self::\\1',
                '*[last()]/self::\\1',
                '*[@\\1]',
                '\\1[@\\2]',
                '\\1[@\\2="\\3"]',
            ];

            $cssSelector = '//' . preg_replace($search, $replace, $cssSelector);

            $cssSelector = preg_replace_callback(
                self::ID_ATTRIBUTE_MATCHER,
                [$this, 'matchIdAttributes'],
                $cssSelector
            );
            $cssSelector = preg_replace_callback(
                self::CLASS_ATTRIBUTE_MATCHER,
                [$this, 'matchClassAttributes'],
                $cssSelector
            );

            // Advanced selectors are going to require a bit more advanced emogrification.
            // When we required PHP 5.3, we could do this with closures.
            $cssSelector = preg_replace_callback(
                '/([^\\/]+):nth-child\\(\\s*(odd|even|[+\\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i',
                [$this, 'translateNthChild'],
                $cssSelector
            );
            $cssSelector = preg_replace_callback(
                '/([^\\/]+):nth-of-type\\(\s*(odd|even|[+\\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i',
                [$this, 'translateNthOfType'],
                $cssSelector
            );

            $this->caches[self::CACHE_KEY_SELECTOR][$xpathKey] = $cssSelector;
        }
        return $this->caches[self::CACHE_KEY_SELECTOR][$xpathKey];
    }

    /**
     * @param string[] $match
     *
     * @return string
     */
    private function matchIdAttributes(array $match)
    {
        return ($match[1] !== '' ? $match[1] : '*') . '[@id="' . $match[2] . '"]';
    }

    /**
     * @param string[] $match
     *
     * @return string
     */
    private function matchClassAttributes(array $match)
    {
        return ($match[1] !== '' ? $match[1] : '*') . '[contains(concat(" ",@class," "),concat(" ","' .
            implode(
                '"," "))][contains(concat(" ",@class," "),concat(" ","',
                explode('.', substr($match[2], 1))
            ) . '"," "))]';
    }

    /**
     * @param string[] $match
     *
     * @return string
     */
    private function translateNthChild(array $match)
    {
        $parseResult = $this->parseNth($match);

        if (isset($parseResult[self::MULTIPLIER])) {
            if ($parseResult[self::MULTIPLIER] < 0) {
                $parseResult[self::MULTIPLIER] = abs($parseResult[self::MULTIPLIER]);
                $xPathExpression = sprintf(
                    '*[(last() - position()) mod %u = %u]/self::%s',
                    $parseResult[self::MULTIPLIER],
                    $parseResult[self::INDEX],
                    $match[1]
                );
            } else {
                $xPathExpression = sprintf(
                    '*[position() mod %u = %u]/self::%s',
                    $parseResult[self::MULTIPLIER],
                    $parseResult[self::INDEX],
                    $match[1]
                );
            }
        } else {
            $xPathExpression = sprintf('*[%u]/self::%s', $parseResult[self::INDEX], $match[1]);
        }

        return $xPathExpression;
    }

    /**
     * @param string[] $match
     *
     * @return string
     */
    private function translateNthOfType(array $match)
    {
        $parseResult = $this->parseNth($match);

        if (isset($parseResult[self::MULTIPLIER])) {
            if ($parseResult[self::MULTIPLIER] < 0) {
                $parseResult[self::MULTIPLIER] = abs($parseResult[self::MULTIPLIER]);
                $xPathExpression = sprintf(
                    '%s[(last() - position()) mod %u = %u]',
                    $match[1],
                    $parseResult[self::MULTIPLIER],
                    $parseResult[self::INDEX]
                );
            } else {
                $xPathExpression = sprintf(
                    '%s[position() mod %u = %u]',
                    $match[1],
                    $parseResult[self::MULTIPLIER],
                    $parseResult[self::INDEX]
                );
            }
        } else {
            $xPathExpression = sprintf('%s[%u]', $match[1], $parseResult[self::INDEX]);
        }

        return $xPathExpression;
    }

    /**
     * @param string[] $match
     *
     * @return int[]
     */
    private function parseNth(array $match)
    {
        if (in_array(strtolower($match[2]), ['even','odd'], true)) {
            $index = strtolower($match[2]) === 'even' ? 0 : 1;
            $result = [self::MULTIPLIER => 2, self::INDEX => $index];
        } elseif (stripos($match[2], 'n') === false) {
            // if there is a multiplier
            $index = (int) str_replace(' ', '', $match[2]);
            $result = [self::INDEX => $index];
        } else {
            if (isset($match[3])) {
                $multipleTerm = str_replace($match[3], '', $match[2]);
                $index = (int) str_replace(' ', '', $match[3]);
            } else {
                $multipleTerm = $match[2];
                $index = 0;
            }

            $multiplier = (int) str_ireplace('n', '', $multipleTerm);

            if (!strlen($multiplier)) {
                $multiplier = 1;
            } elseif ($multiplier === 0) {
                return [self::INDEX => $index];
            } else {
                $multiplier = (int) $multiplier;
            }

            while ($index < 0) {
                $index += abs($multiplier);
            }

            $result = [self::MULTIPLIER => $multiplier, self::INDEX => $index];
        }

        return $result;
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
     * @param string $cssDeclarationBlock the CSS declaration block without the curly braces, may be empty
     *
     * @return string[]
     *         the CSS declarations with the property names as array keys and the property values as array values
     */
    private function parseCssDeclarationBlock($cssDeclarationBlock)
    {
        if (isset($this->caches[self::CACHE_KEY_CSS_DECLARATION_BLOCK][$cssDeclarationBlock])) {
            return $this->caches[self::CACHE_KEY_CSS_DECLARATION_BLOCK][$cssDeclarationBlock];
        }

        $properties = [];
        $declarations = explode(';', $cssDeclarationBlock);
        foreach ($declarations as $declaration) {
            $matches = [];
            if (!preg_match('/ *([A-Za-z\\-]+) *: *([^;]+) */', $declaration, $matches)) {
                continue;
            }
            $propertyName = strtolower($matches[1]);
            $propertyValue = $matches[2];
            $properties[$propertyName] = $propertyValue;
        }
        $this->caches[self::CACHE_KEY_CSS_DECLARATION_BLOCK][$cssDeclarationBlock] = $properties;

        return $properties;
    }

    /**
     * Find the nodes that are not to be emogrified.
     *
     * @param \DOMXPath $xpath
     *
     * @return \DOMElement[]
     */
    private function getNodesToExclude(\DOMXPath $xpath)
    {
        $excludedNodes = [];
        foreach (array_keys($this->excludedSelectors) as $selectorToExclude) {
            foreach ($xpath->query($this->translateCssToXpath($selectorToExclude)) as $node) {
                $excludedNodes[] = $node;
            }
        }

        return $excludedNodes;
    }
}
