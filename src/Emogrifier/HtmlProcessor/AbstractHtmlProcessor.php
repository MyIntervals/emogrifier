<?php

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
    const DEFAULT_DOCUMENT_TYPE = '<!DOCTYPE html>';

    /**
     * @var string
     */
    const CONTENT_TYPE_META_TAG = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';

    /**
     * @var \DOMDocument
     */
    protected $xmlDocument = null;

    /**
     * @param string $unprocessedHtml raw HTML, will get heavily normalized
     *
     * @throws \InvalidArgumentException if $unprocessedHtml is anything other than a non-empty string
     */
    public function __construct($unprocessedHtml)
    {
        if (!is_string($unprocessedHtml)) {
            throw new \InvalidArgumentException('The provided HTML must be a string.', 1515459744);
        }
        if ($unprocessedHtml === '') {
            throw new \InvalidArgumentException('The provided HTML must not be empty.', 1515763647);
        }

        $this->xmlDocument = $this->createXmlDocument($unprocessedHtml);
    }

    /**
     * Provides access to the internal DOMDocument representation of the HTML in its current state.
     *
     * @return \DOMDocument
     */
    public function getDomDocument()
    {
        return $this->xmlDocument;
    }

    /**
     * Renders the normalized and processed HTML.
     *
     * @return string
     */
    public function render()
    {
        return $this->xmlDocument->saveHTML();
    }

    /**
     * Creates a DOMDocument from $html.
     *
     * @param string $html
     *
     * @return \DOMDocument
     */
    private function createXmlDocument($html)
    {
        $xmlDocument = new \DOMDocument;
        $xmlDocument->strictErrorChecking = false;
        $xmlDocument->formatOutput = true;
        $libXmlState = libxml_use_internal_errors(true);
        $xmlDocument->loadHTML($this->unifyHtml($html));
        libxml_clear_errors();
        libxml_use_internal_errors($libXmlState);

        $this->ensureExistenceOfBodyElement($xmlDocument);

        return $xmlDocument;
    }

    /**
     * Returns the HTML with added document type and Content-Type meta tag (if any of this is missing).
     *
     * @param string $html
     *
     * @return string the unified HTML
     */
    private function unifyHtml($html)
    {
        $htmlWithDocumentType = $this->ensureDocumentType($html);

        return $this->addContentTypeMetaTag($htmlWithDocumentType);
    }

    /**
     * Makes sure that the passed HTML has a document type.
     *
     * @param string $html
     *
     * @return string HTML with document type
     */
    private function ensureDocumentType($html)
    {
        $hasDocumentType = stripos($html, '<!DOCTYPE') !== false;
        if ($hasDocumentType) {
            return $html;
        }

        return static::DEFAULT_DOCUMENT_TYPE . $html;
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
        $hasContentTypeMetaTag = stripos($html, 'Content-Type') !== false;
        if ($hasContentTypeMetaTag) {
            return $html;
        }

        // We are trying to insert the meta tag to the right spot in the DOM.
        // If we just prepended it to the HTML, we would lose attributes set to the HTML tag.
        $hasHeadTag = stripos($html, '<head') !== false;
        $hasHtmlTag = stripos($html, '<html') !== false;

        if ($hasHeadTag) {
            $reworkedHtml = preg_replace('/<head(.*?)>/i', '<head$1>' . static::CONTENT_TYPE_META_TAG, $html);
        } elseif ($hasHtmlTag) {
            $reworkedHtml = preg_replace(
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
     * Checks that $document has a BODY element and adds it if it is missing.
     *
     * @param \DOMDocument $document
     *
     * @return void
     */
    private function ensureExistenceOfBodyElement(\DOMDocument $document)
    {
        if ($document->getElementsByTagName('body')->item(0) !== null) {
            return;
        }

        $htmlElement = $document->getElementsByTagName('html')->item(0);
        $htmlElement->appendChild($document->createElement('body'));
    }
}
