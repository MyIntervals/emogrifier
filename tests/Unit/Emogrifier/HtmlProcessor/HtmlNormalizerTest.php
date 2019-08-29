<?php

namespace Pelago\Tests\Unit\Emogrifier\HtmlProcessor;

use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\HtmlProcessor\HtmlNormalizer;

/**
 * Test case.
 *
 * @author Oliver Klee <github@oliverklee.de>
 */
class HtmlNormalizerTest extends \PHPUnit_Framework_TestCase
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
