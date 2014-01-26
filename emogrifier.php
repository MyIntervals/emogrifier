<?php

define('CACHE_CSS', 0);
define('CACHE_SELECTOR', 1);
define('CACHE_XPATH', 2);

class Emogrifier {
    /**
     * for calculating nth-of-type and nth-child selectors
     *
     * @var integer
     */
    const INDEX = 0;

    /**
     * for calculating nth-of-type and nth-child selectors
     *
     * @var integer
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
    private $html = '';

    /**
     * @var string
     */
    private $css = '';

    /**
     * @var array<string>
     */
    private $unprocessableHtmlTags = array('wbr');

    /**
     * @var array<array>
     */
    private $caches = array();

    /**
     * This attribute applies to the case where you want to preserve your original text encoding.
     *
     * By default, emogrifier translates your text into HTML entities for two reasons:
     *
     * 1. Because of client incompatibilities, it is better practice to send out HTML entities rather than unicode over email.
     *
     * 2. It translates any illegal XML characters that DOMDocument cannot work with.
     *
     * If you would like to preserve your original encoding, set this attribute to TRUE.
     *
     * @var boolean
     */
    public $preserveEncoding = FALSE;

    /**
     * @param string $html
     * @param string $css
     */
    public function __construct($html = '', $css = '') {
        $this->setHtml($html);
        $this->setCss($css);
    }

    /**
     * @param string $html
     *
     * @return void
     */
    public function setHtml($html = '') {
        $this->html = $html;
    }

    /**
     * @param string $css
     *
     * @return void
     */
    public function setCss($css = '') {
        $this->css = $css;
        $this->clearCache(CACHE_CSS);
    }

    /**
     * @param integer|NULL $key
     *
     * @return void
     */
    public function clearCache($key = NULL) {
        if (!is_null($key)) {
            if (isset($this->caches[$key])) {
                $this->caches[$key] = array();
            }
        } else {
            $this->caches = array(
                CACHE_CSS       => array(),
                CACHE_SELECTOR  => array(),
                CACHE_XPATH     => array(),
            );
        }
    }

    /**
     * There are some HTML tags that DOMDocument cannot process, and it will throw an error if it encounters them.
     * In particular, DOMDocument will complain if you try to use HTML5 tags in an XHTML document.
     *
     * This method allows you to add them if necessary.
     *
     * It only strips them from the code (i.e., it does not actually remove any nodes).
     *
     * @param string $tag
     *
     * @return void
     */
    public function addUnprocessableHtmlTag($tag) {
        $this->unprocessableHtmlTags[] = $tag;
    }

    /**
     * There are some HTML tags that DOMDocument cannot process, and it will throw an error if it encounters them.
     * In particular, DOMDocument will complain if you try to use HTML5 tags in an XHTML document.
     *
     * This method allows you to remove them if necessary.
     *
     * It only strips them from the code (i.e., it does not actually remove any nodes).
     *
     * @param string $tag
     *
     * @return void
     */
    public function removeUnprocessableHtmlTag($tag) {
        if (($key = array_search($tag, $this->unprocessableHtmlTags)) !== FALSE) {
            unset($this->unprocessableHtmlTags[$key]);
        }
    }

    /**
     * Applies the CSS you submit to the HTML you submit.
     *
     * This method places the CSS inline.
     *
     * @return string
     *
     * @throws BadMethodCallException
     */
    public function emogrify() {
        if ($this->html === '') {
            throw new BadMethodCallException('Please set some HTML first before calling emogrify.', 1390393096);
        }

        $body = $this->html;

        // remove any unprocessable HTML tags (tags that DOMDocument cannot parse; this includes wbr and many new HTML5 tags)
        if (count($this->unprocessableHtmlTags)) {
            $unprocessableHtmlTags = implode('|', $this->unprocessableHtmlTags);
            $body = preg_replace("/<\\/?($unprocessableHtmlTags)[^>]*>/i", '', $body);
        }

        $encoding = mb_detect_encoding($body);
        $body = mb_convert_encoding($body, 'HTML-ENTITIES', $encoding);

        $xmlDocument = new DOMDocument;
        $xmlDocument->encoding = $encoding;
        $xmlDocument->strictErrorChecking = FALSE;
        $xmlDocument->formatOutput = TRUE;
        $xmlDocument->loadHTML($body);
        $xmlDocument->normalizeDocument();

        $xpath = new DOMXPath($xmlDocument);

        // before be begin processing the CSS file, parse the document and normalize all existing CSS attributes (changes 'DISPLAY: none' to 'display: none');
        // we wouldn't have to do this if DOMXPath supported XPath 2.0.
        // also store a reference of nodes with existing inline styles so we don't overwrite them
        $visitedNodes = $visitedNodeReferences = array();
        $nodes = @$xpath->query('//*[@style]');
        foreach ($nodes as $node) {
            $normalizedOrigStyle = preg_replace_callback('/[A-z\\-]+(?=\\:)/S', create_function('$m', 'return strtolower($m[0]);'), $node->getAttribute('style'));

            // in order to not overwrite existing style attributes in the HTML, we have to save the original HTML styles
            $nodeKey = md5($node->getNodePath());
            if (!isset($visitedNodeReferences[$nodeKey])) {
                $visitedNodeReferences[$nodeKey] = $this->cssStyleDefinitionToArray($normalizedOrigStyle);
                $visitedNodes[$nodeKey]   = $node;
            }

            $node->setAttribute('style', $normalizedOrigStyle);
        }

        // grab any existing style blocks from the html and append them to the existing CSS
        // (these blocks should be appended so as to have precedence over conflicting styles in the existing CSS)
        $css = $this->css;
        $nodes = @$xpath->query('//style');
        foreach ($nodes as $node) {
            // append the css
            $css .= "\n\n{$node->nodeValue}";
            // remove the <style> node
            $node->parentNode->removeChild($node);
        }

        // filter the CSS
        $search = array(
            // get rid of css comment code
            '/\\/\\*.*\\*\\//sU',
            // strip out any import directives
            '/^\\s*@import\\s[^;]+;/misU',
            // strip any empty media enclosures
            '/^\\s*@media\\s[^{]+{\\s*}/misU',
            // strip out all media types that are not 'screen' or 'all' (these don't apply to email)
            '/^\\s*@media\\s+((aural|braille|embossed|handheld|print|projection|speech|tty|tv)\\s*,*\\s*)+{.*}\\s*}/misU',
            // get rid of remaining media type enclosures
            '/^\\s*@media\\s[^{]+{(.*})\\s*}/misU',
        );

        $replace = array(
            '',
            '',
            '',
            '',
            '\\1',
        );

        $css = preg_replace($search, $replace, $css);

        $cssKey = md5($css);
        if (!isset($this->caches[CACHE_CSS][$cssKey])) {
            // process the CSS file for selectors and definitions
            preg_match_all('/(^|[^{}])\\s*([^{]+){([^}]*)}/mis', $css, $matches, PREG_SET_ORDER);

            $allSelectors = array();
            foreach ($matches as $key => $selectorString) {
                // if there is a blank definition, skip
                if (!strlen(trim($selectorString[3]))) {
                    continue;
                }

                // else split by commas and duplicate attributes so we can sort by selector precedence
                $selectors = explode(',', $selectorString[2]);
                foreach ($selectors as $selector) {
                    // don't process pseudo-elements and behavioral (dynamic) pseudo-classes; ONLY allow structural pseudo-classes
                    if (strpos($selector, ':') !== FALSE && !preg_match('/:\\S+\\-(child|type)\\(/i', $selector)) {
                        continue;
                    }

                    $allSelectors[] = array('selector' => trim($selector),
                                             'attributes' => trim($selectorString[3]),
                                             // keep track of where it appears in the file, since order is important
                                             'line' => $key,
                    );
                }
            }

            // now sort the selectors by precedence
            usort($allSelectors, array($this,'sortBySelectorPrecedence'));

            $this->caches[CACHE_CSS][$cssKey] = $allSelectors;
        }

        foreach ($this->caches[CACHE_CSS][$cssKey] as $value) {
            // query the body for the xpath selector
            $nodes = $xpath->query($this->translateCssToXpath(trim($value['selector'])));

            foreach ($nodes as $node) {
                // if it has a style attribute, get it, process it, and append (overwrite) new stuff
                if ($node->hasAttribute('style')) {
                    // break it up into an associative array
                    $oldStyleArr = $this->cssStyleDefinitionToArray($node->getAttribute('style'));
                    $newStyleArr = $this->cssStyleDefinitionToArray($value['attributes']);

                    // new styles overwrite the old styles (not technically accurate, but close enough)
                    $combinedArray = array_merge($oldStyleArr, $newStyleArr);
                    $style = '';
                    foreach ($combinedArray as $k => $v) {
                        $style .= (strtolower($k) . ':' . $v . ';');
                    }
                } else {
                    // otherwise create a new style
                    $style = trim($value['attributes']);
                }
                $node->setAttribute('style', $style);
            }
        }

        // now iterate through the nodes that contained inline styles in the original HTML
        foreach ($visitedNodeReferences as $nodeKey => $originalStyleArray) {
            $node = $visitedNodes[$nodeKey];
            $currentStyleArray = $this->cssStyleDefinitionToArray($node->getAttribute('style'));

            $combinedArray = array_merge($currentStyleArray, $originalStyleArray);
            $style = '';
            foreach ($combinedArray as $k => $v) {
                $style .= (strtolower($k) . ':' . $v . ';');
            }

            $node->setAttribute('style', $style);
        }

        // This removes styles from your email that contain display:none.
        // We need to look for display:none, but we need to do a case-insensitive search. Since DOMDocument only supports XPath 1.0,
        // lower-case() isn't available to us. We've thus far only set attributes to lowercase, not attribute values. Consequently, we need
        // to translate() the letters that would be in 'NONE' ("NOE") to lowercase.
        $nodes = $xpath->query('//*[contains(translate(translate(@style," ",""),"NOE","noe"),"display:none")]');
        // The checks on parentNode and is_callable below ensure that if we've deleted the parent node,
        // we don't try to call removeChild on a nonexistent child node
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                if ($node->parentNode && is_callable(array($node->parentNode,'removeChild'))) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        if ($this->preserveEncoding) {
            return mb_convert_encoding($xmlDocument->saveHTML(), $encoding, 'HTML-ENTITIES');
        } else {
            return $xmlDocument->saveHTML();
        }
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return integer
     */
    private function sortBySelectorPrecedence(array $a, array $b) {
        $precedenceA = $this->getCssSelectorPrecedence($a['selector']);
        $precedenceB = $this->getCssSelectorPrecedence($b['selector']);

        // We want these sorted in ascending order so selectors with lesser precedence get processed first and
        // selectors with greater precedence get sorted last.
        return ($precedenceA == $precedenceB) ? ($a['line'] < $b['line'] ? -1 : 1) : ($precedenceA < $precedenceB ? -1 : 1);
    }

    /**
     * @param string $selector
     *
     * @return integer
     */
    private function getCssSelectorPrecedence($selector) {
        $selectorKey = md5($selector);
        if (!isset($this->caches[CACHE_SELECTOR][$selectorKey])) {
            $precedence = 0;
            $value = 100;
            // ids: worth 100, classes: worth 10, elements: worth 1
            $search = array('\\#','\\.','');

            foreach ($search as $s) {
                if (trim($selector == '')) {
                    break;
                }
                $number = 0;
                $selector = preg_replace('/' . $s . '\\w+/', '', $selector, -1, $number);
                $precedence += ($value * $number);
                $value /= 10;
            }
            $this->caches[CACHE_SELECTOR][$selectorKey] = $precedence;
        }

        return $this->caches[CACHE_SELECTOR][$selectorKey];
    }

    /**
     * Right now, we support all CSS 1 selectors and most CSS2/3 selectors.
     *
     * @see http://plasmasturm.org/log/444/
     *
     * @param string $cssSelector
     *
     * @return string
     */
    private function translateCssToXpath($cssSelector) {
        $cssSelector = trim($cssSelector);
        $xpathKey = md5($cssSelector);
        if (!isset($this->caches[CACHE_XPATH][$xpathKey])) {
            // returns an Xpath selector
            $search = array(
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
                // Matches element with attribute
                '/(\\w)\\[(\\w+)\\]/',
                // Matches element with EXACT attribute
                '/(\\w)\\[(\\w+)\\=[\'"]?(\\w+)[\'"]?\\]/',
            );
            $replace = array(
                '/',
                '/following-sibling::*[1]/self::',
                '//',
                '*[1]/self::\\1',
                '*[last()]/self::\\1',
                '\\1[@\\2]',
                '\\1[@\\2="\\3"]',
            );

            $cssSelector = '//' . preg_replace($search, $replace, $cssSelector);

            $cssSelector = preg_replace_callback(self::ID_ATTRIBUTE_MATCHER, array($this, 'matchIdAttributes'), $cssSelector);
            $cssSelector = preg_replace_callback(self::CLASS_ATTRIBUTE_MATCHER, array($this, 'matchClassAttributes'), $cssSelector);

            // Advanced selectors are going to require a bit more advanced emogrification.
            // When we required PHP 5.3, we could do this with closures.
            $cssSelector = preg_replace_callback(
                '/([^\\/]+):nth-child\\(\s*(odd|even|[+\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i',
                array($this, 'translateNthChild'), $cssSelector
            );
            $cssSelector = preg_replace_callback(
                '/([^\\/]+):nth-of-type\\(\s*(odd|even|[+\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i',
                array($this, 'translateNthOfType'), $cssSelector
            );

            $this->caches[CACHE_SELECTOR][$xpathKey] = $cssSelector;
        }
        return $this->caches[CACHE_SELECTOR][$xpathKey];
    }

    /**
     * @param array $match
     *
     * @return string
     */
    private function matchIdAttributes(array $match) {
        return (strlen($match[1]) ? $match[1] : '*') . '[@id="' . $match[2] . '"]';
    }

    /**
     * @param array $match
     *
     * @return string
     */
    private function matchClassAttributes(array $match) {
        return (strlen($match[1]) ? $match[1] : '*') . '[contains(concat(" ",@class," "),concat(" ","' .
            implode(
                '"," "))][contains(concat(" ",@class," "),concat(" ","',
                explode('.', substr($match[2], 1))
            ) . '"," "))]';
    }

    /**
     * @param array $match
     *
     * @return string
     */
    private function translateNthChild(array $match) {
        $result = $this->parseNth($match);

        if (isset($result[self::MULTIPLIER])) {
            if ($result[self::MULTIPLIER] < 0) {
                $result[self::MULTIPLIER] = abs($result[self::MULTIPLIER]);
                return sprintf("*[(last() - position()) mod %u = %u]/self::%s", $result[self::MULTIPLIER], $result[self::INDEX], $match[1]);
            } else {
                return sprintf("*[position() mod %u = %u]/self::%s", $result[self::MULTIPLIER], $result[self::INDEX], $match[1]);
            }
        } else {
            return sprintf("*[%u]/self::%s", $result[self::INDEX], $match[1]);
        }
    }

    /**
     * @param array $match
     *
     * @return string
     */
    private function translateNthOfType(array $match) {
        $result = $this->parseNth($match);

        if (isset($result[self::MULTIPLIER])) {
            if ($result[self::MULTIPLIER] < 0) {
                $result[self::MULTIPLIER] = abs($result[self::MULTIPLIER]);
                return sprintf("%s[(last() - position()) mod %u = %u]", $match[1], $result[self::MULTIPLIER], $result[self::INDEX]);
            } else {
                return sprintf("%s[position() mod %u = %u]", $match[1], $result[self::MULTIPLIER], $result[self::INDEX]);
            }
        } else {
            return sprintf("%s[%u]", $match[1], $result[self::INDEX]);
        }
    }

    /**
     * @param array $match
     *
     * @return array
     */
    private function parseNth(array $match) {
        if (in_array(strtolower($match[2]), array('even','odd'))) {
            $index = strtolower($match[2]) == 'even' ? 0 : 1;
            return array(self::MULTIPLIER => 2, self::INDEX => $index);
        } elseif (stripos($match[2], 'n') === FALSE) {
            // if there is a multiplier
            $index = intval(str_replace(' ', '', $match[2]));
            return array(self::INDEX => $index);
        } else {
            if (isset($match[3])) {
                $multipleTerm = str_replace($match[3], '', $match[2]);
                $index = intval(str_replace(' ', '', $match[3]));
            } else {
                $multipleTerm = $match[2];
                $index = 0;
            }

            $multiplier = str_ireplace('n', '', $multipleTerm);

            if (!strlen($multiplier)) {
                $multiplier = 1;
            } elseif ($multiplier == 0) {
                return array(self::INDEX => $index);
            } else {
                $multiplier = intval($multiplier);
            }

            while ($index < 0) {
                $index += abs($multiplier);
            }

            return array(self::MULTIPLIER => $multiplier, self::INDEX => $index);
        }
    }

    /**
     * @param string $style
     *
     * @return array
     */
    private function cssStyleDefinitionToArray($style) {
        $definitions = explode(';', $style);
        $returnArray = array();

        foreach ($definitions as $definition) {
            if (empty($definition) || strpos($definition, ':') === FALSE) {
                continue;
            }
            list($key, $value) = explode(':', $definition, 2);
            if (empty($key) || strlen(trim($value)) === 0) {
                continue;
            }
            $returnArray[trim($key)] = trim($value);
        }

        return $returnArray;
    }
}
