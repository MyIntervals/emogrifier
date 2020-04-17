<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Factories;

use DOMDocument;
use Pelago\Emogrifier\CssInliner;

/**
 * This class provides a factory for CssInliner that can be passed as a dependency
 * or be injected via dependency injection
 *
 * @author SpazzMarticus <SpazzMarticus@users.noreply.github.com>
 */
class CssInlinerFactory
{
    /**
     * Creates a new instance of CssInliner from a string
     *
     * @param string $unprocessedHtml
     *
     * @return CssInliner
     *
     * @throws \InvalidArgumentException if $unprocessedHtml is anything other than a non-empty string
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createFromHtml(string $unprocessedHtml): CssInliner
    {
        return CssInliner::fromHtml($unprocessedHtml);
    }

    /**
     * Creates a new instance of CssInliner from a DOM document
     *
     * @param \DOMDocument $document a DOM document returned by getDomDocument() of another instance
     *
     * @return CssInliner
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createFromDomDocument(DOMDocument $document): CssInliner
    {
        return CssInliner::fromDomDocument($document);
    }
}
