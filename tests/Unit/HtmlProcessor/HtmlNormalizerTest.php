<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\HtmlProcessor;

use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\HtmlProcessor\HtmlNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <github@oliverklee.de>
 */
class HtmlNormalizerTest extends TestCase
{
    /**
     * @test
     */
    public function fromHtmlReturnsInstanceOfCalledClass()
    {
        $subject = HtmlNormalizer::fromHtml('<html></html>');

        self::assertInstanceOf(HtmlNormalizer::class, $subject);
    }

    /**
     * @test
     */
    public function classIsAbstractHtmlProcessor()
    {
        $subject = HtmlNormalizer::fromHtml('<html></html>');

        self::assertInstanceOf(AbstractHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromDomDocumentReturnsInstanceOfCalledClass()
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = HtmlNormalizer::fromDomDocument($document);

        self::assertInstanceOf(HtmlNormalizer::class, $subject);
    }
}
