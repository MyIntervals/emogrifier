<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\HtmlProcessor;

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
     *
     * @return static
     *
     * @throws \InvalidArgumentException if $unprocessedHtml is anything other than a non-empty string
     */
    public static function fromHtml(string $unprocessedHtml): self
    {
        if ($unprocessedHtml === '') {
            throw new \InvalidArgumentException('The provided HTML must not be empty.', 1515763647);
        }

        $instance = new static();
        $instance->setHtml($unprocessedHtml);

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
     *
     * @return void
     */
    private function setHtml(string $html): void
    {
        $this->createUnifiedDomDocument($html);
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
     *
     * @return void
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
        $htmlWithPossibleErroneousClosingTags = $this->getDomDocument()->saveHTML();

        return $this->removeSelfClosingTagsClosingTags($htmlWithPossibleErroneousClosingTags);
    }

    /**
     * Renders the content of the BODY element of the normalized and processed HTML.
     *
     * @return string
     */
    public function renderBodyContent(): string
    {
        $htmlWithPossibleErroneousClosingTags = $this->getDomDocument()->saveHTML($this->getBodyElement());
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
     *
     * @return void
     */
    private function createUnifiedDomDocument(string $html): void
    {
        $this->createRawDomDocument($html);
        $this->ensureExistenceOfBodyElement();
    }

    /**
     * Creates a DOMDocument instance from the given HTML and stores it in $this->domDocument.
     *
     * @param string $html
     *
     * @return void
     */
    private function createRawDomDocument(string $html): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->strictErrorChecking = false;
        $domDocument->formatOutput = true;
        $libXmlState = \libxml_use_internal_errors(true);
        $domDocument->loadHTML($this->prepareHtmlForDomConversion($html));
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
     * @return void
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
}
