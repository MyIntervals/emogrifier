<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\HtmlProcessor;

use DOMNode;
use Masterminds\HTML5;

/**
 * Base class for HTML processor that e.g., can remove, add or modify nodes or attributes.
 *
 * The "vanilla" subclass is the HtmlNormalizer.
 *
 * @author Oliver Klee <github@oliverklee.de>
 */
abstract class AbstractHtmlProcessor
{
    /**
     * @var string
     */
    protected const DEFAULT_DOCUMENT_TYPE = '<!DOCTYPE html>';

    /**
     * @var string
     */
    protected const CONTENT_TYPE_META_TAG = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';

    /**
     * @var string Regular expression part to match tag names that PHP's DOMDocument implementation is not aware are
     *      self-closing. These are mostly HTML5 elements, but for completeness <command> (obsolete) and <keygen>
     *      (deprecated) are also included.
     *
     * @see https://bugs.php.net/bug.php?id=73175
     */
    protected const PHP_UNRECOGNIZED_VOID_TAGNAME_MATCHER = '(?:command|embed|keygen|source|track|wbr)';

    /**
     * @var \DOMDocument|null
     */
    protected $domDocument = null;

    /**
     * @var HTML5|null
     */
    protected $html5 = null;

    /**
     * @var \DOMXPath
     */
    protected $xPath = null;

    /**
     * The constructor.
     *
     * Please use `::fromHtml` or `::fromDomDocument` instead.
     */
    private function __construct()
    {
    }

    /**
     * Builds a new instance from the given HTML.
     *
     * @param string $unprocessedHtml raw HTML, must be UTF-encoded, must not be empty
     * @param bool $html5 use masterminds/html5 parser instead of DOMDocument.
     *
     * @return static
     *
     * @throws \InvalidArgumentException if $unprocessedHtml is anything other than a non-empty string
     */
    public static function fromHtml(string $unprocessedHtml, ?bool $html5 = null): self
    {
        if ($unprocessedHtml === '') {
            throw new \InvalidArgumentException('The provided HTML must not be empty.', 1515763647);
        }

        $instance = new static();
        $instance->setHtml($unprocessedHtml, $html5);

        return $instance;
    }

    /**
     * Builds a new instance from the given DOM document.
     *
     * @param \DOMDocument $document a DOM document returned by getDomDocument() of another instance
     *
     * @return static
     */
    public static function fromDomDocument(\DOMDocument $document): self
    {
        $instance = new static();
        $instance->setDomDocument($document);

        return $instance;
    }

    /**
     * Sets the HTML to process.
     *
     * @param string $html the HTML to process, must be UTF-8-encoded
     * @param bool $html5 use masterminds/html5 parser instead of DOMDocument.
     */
    private function setHtml(string $html, ?bool $html5): void
    {
        // If html5 is NULL, fallback to the environment flag.
        $html5 = $html5 ?? $this->isHtml5Env();

        $this->createUnifiedDomDocument($html, $html5);
    }

    /**
     * Provides access to the internal DOMDocument representation of the HTML in its current state.
     *
     * @return \DOMDocument
     *
     * @throws \UnexpectedValueException
     */
    public function getDomDocument(): \DOMDocument
    {
        if ($this->domDocument === null) {
            throw new \UnexpectedValueException(
                (
                    self::class .
                    '::setDomDocument() has not yet been called on ' .
                    static::class
                ),
                1570472239
            );
        }

        return $this->domDocument;
    }

    /**
     * @param \DOMDocument $domDocument
     */
    private function setDomDocument(\DOMDocument $domDocument): void
    {
        $this->domDocument = $domDocument;
        $this->xPath = new \DOMXPath($this->domDocument);
    }

    /**
     * Renders the normalized and processed HTML.
     *
     * @return string
     */
    public function render(): string
    {
        $htmlWithPossibleErroneousClosingTags = $this->saveHTML();

        return $this->removeSelfClosingTagsClosingTags($htmlWithPossibleErroneousClosingTags);
    }

    /**
     * Renders the content of the BODY element of the normalized and processed HTML.
     *
     * @return string
     */
    public function renderBodyContent(): string
    {
        $htmlWithPossibleErroneousClosingTags = $this->saveHTML($this->getBodyElement());
        $bodyNodeHtml = $this->removeSelfClosingTagsClosingTags($htmlWithPossibleErroneousClosingTags);

        return \preg_replace('%</?+body(?:\\s[^>]*+)?+>%', '', $bodyNodeHtml);
    }

    /**
     * Eliminates any invalid closing tags for void elements from the given HTML.
     *
     * @param string $html
     *
     * @return string
     */
    private function removeSelfClosingTagsClosingTags(string $html): string
    {
        return \preg_replace('%</' . static::PHP_UNRECOGNIZED_VOID_TAGNAME_MATCHER . '>%', '', $html);
    }

    /**
     * Returns the BODY element.
     *
     * This method assumes that there always is a BODY element.
     *
     * @return \DOMElement
     */
    private function getBodyElement(): \DOMElement
    {
        return $this->getDomDocument()->getElementsByTagName('body')->item(0);
    }

    /**
     * Creates a DOM document from the given HTML and stores it in $this->domDocument.
     *
     * The DOM document will always have a BODY element and a document type.
     *
     * @param string $html
     * @param bool $html5
     */
    private function createUnifiedDomDocument(string $html, bool $html5): void
    {
        $html = $this->prepareHtmlForDomConversion($html);

        $html5 ? $this->createHtml5Document($html) : $this->createRawDomDocument($html);

        $this->ensureExistenceOfBodyElement();
    }

    /**
     * Creates a HTML5 document parser instance from the given HTML.
     *
     * @param string $html
     *
     * @throws \RuntimeException
     */
    private function createHtml5Document(string $html): void
    {
        if (! class_exists(HTML5::class)) {
            throw new \RuntimeException("Class " . HTML5::class . "not found. Install the masterminds/html5 library.");
        }

        $this->html5 = new HTML5(['disable_html_ns' => true]);
        $domDocument = $this->html5->parse($html);

        $this->setDomDocument($domDocument);
    }

    /**
     * Creates a DOMDocument instance from the given HTML and stores it in $this->domDocument.
     *
     * @param string $html
     */
    private function createRawDomDocument(string $html): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->strictErrorChecking = false;
        $domDocument->formatOutput = true;
        $libXmlState = \libxml_use_internal_errors(true);
        $domDocument->loadHTML($html);
        \libxml_clear_errors();
        \libxml_use_internal_errors($libXmlState);

        $this->setDomDocument($domDocument);
    }

    /**
     * Returns the HTML with added document type, Content-Type meta tag, and self-closing slashes, if needed,
     * ensuring that the HTML will be good for creating a DOM document from it.
     *
     * @param string $html
     *
     * @return string the unified HTML
     */
    private function prepareHtmlForDomConversion(string $html): string
    {
        $htmlWithSelfClosingSlashes = $this->ensurePhpUnrecognizedSelfClosingTagsAreXml($html);
        $htmlWithDocumentType = $this->ensureDocumentType($htmlWithSelfClosingSlashes);

        return $this->addContentTypeMetaTag($htmlWithDocumentType);
    }

    /**
     * Makes sure that the passed HTML has a document type, with lowercase "html".
     *
     * @param string $html
     *
     * @return string HTML with document type
     */
    private function ensureDocumentType(string $html): string
    {
        $hasDocumentType = \stripos($html, '<!DOCTYPE') !== false;
        if ($hasDocumentType) {
            return $this->normalizeDocumentType($html);
        }

        return static::DEFAULT_DOCUMENT_TYPE . $html;
    }

    /**
     * Makes sure the document type in the passed HTML has lowercase "html".
     *
     * @param string $html
     *
     * @return string HTML with normalized document type
     */
    private function normalizeDocumentType(string $html): string
    {
        // Limit to replacing the first occurrence: as an optimization; and in case an example exists as unescaped text.
        return \preg_replace(
            '/<!DOCTYPE\\s++html(?=[\\s>])/i',
            '<!DOCTYPE html',
            $html,
            1
        );
    }

    /**
     * Adds a Content-Type meta tag for the charset.
     *
     * This method also ensures that there is a HEAD element.
     *
     * @param string $html
     *
     * @return string the HTML with the meta tag added
     */
    private function addContentTypeMetaTag(string $html): string
    {
        $hasContentTypeMetaTag = \stripos($html, 'Content-Type') !== false;
        if ($hasContentTypeMetaTag) {
            return $html;
        }

        // We are trying to insert the meta tag to the right spot in the DOM.
        // If we just prepended it to the HTML, we would lose attributes set to the HTML tag.
        $hasHeadTag = \preg_match('/<head[\\s>]/i', $html);
        $hasHtmlTag = \stripos($html, '<html') !== false;

        if ($hasHeadTag) {
            $reworkedHtml = \preg_replace(
                '/<head(?=[\\s>])([^>]*+)>/i',
                '<head$1>' . static::CONTENT_TYPE_META_TAG,
                $html
            );
        } elseif ($hasHtmlTag) {
            $reworkedHtml = \preg_replace(
                '/<html(.*?)>/i',
                '<html$1><head>' . static::CONTENT_TYPE_META_TAG . '</head>',
                $html
            );
        } else {
            $reworkedHtml = static::CONTENT_TYPE_META_TAG . $html;
        }

        return $reworkedHtml;
    }

    /**
     * Makes sure that any self-closing tags not recognized as such by PHP's DOMDocument implementation have a
     * self-closing slash.
     *
     * @param string $html
     *
     * @return string HTML with problematic tags converted.
     */
    private function ensurePhpUnrecognizedSelfClosingTagsAreXml(string $html): string
    {
        return \preg_replace(
            '%<' . static::PHP_UNRECOGNIZED_VOID_TAGNAME_MATCHER . '\\b[^>]*+(?<!/)(?=>)%',
            '$0/',
            $html
        );
    }

    /**
     * Checks that $this->domDocument has a BODY element and adds it if it is missing.
     *
     * @throws \UnexpectedValueException
     */
    private function ensureExistenceOfBodyElement(): void
    {
        if ($this->getDomDocument()->getElementsByTagName('body')->item(0) !== null) {
            return;
        }

        $htmlElement = $this->getDomDocument()->getElementsByTagName('html')->item(0);
        if ($htmlElement === null) {
            throw new \UnexpectedValueException('There is no HTML element although there should be one.', 1569930853);
        }
        $htmlElement->appendChild($this->getDomDocument()->createElement('body'));
    }

    /**
     * Dumps the internal document into a string using HTML formatting.
     *
     * @param  DOMNode $dom [optional] parameter to output a subset of the document.
     *
     * @return string the HTML, or false if an error occurred.
     */
    private function saveHTML(DOMNode $dom = null): string
    {
        if (isset($this->html5)) {
            if ($dom === null) {
                $dom = $this->domDocument;
            }

            return $this->html5->saveHTML($dom);
        }

        // Fall back to DOMDocument.
        return $this->getDomDocument()->saveHTML($dom);
    }

    /**
     * Check whether HTML5 environment is enabled.
     *
     * @return bool
     */
    private function isHtml5Env(): bool
    {
        return (bool) (getenv('EMOGRIFIER_HTML5') ?? false);
    }
}
