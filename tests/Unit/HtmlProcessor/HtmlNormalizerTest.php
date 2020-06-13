<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\HtmlProcessor;

use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\HtmlProcessor\HtmlNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @covers \Pelago\Emogrifier\HtmlProcessor\HtmlNormalizer
 *
 * @author Oliver Klee <github@oliverklee.de>
 */
class HtmlNormalizerTest extends TestCase
{
    /**
     * @test
     */
    public function fromHtmlReturnsInstanceOfCalledClass(): void
    {
        $subject = HtmlNormalizer::fromHtml('<html></html>');

        self::assertInstanceOf(HtmlNormalizer::class, $subject);
    }

    /**
     * @test
     */
    public function classIsAbstractHtmlProcessor(): void
    {
        $subject = HtmlNormalizer::fromHtml('<html></html>');

        self::assertInstanceOf(AbstractHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromDomDocumentReturnsInstanceOfCalledClass(): void
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = HtmlNormalizer::fromDomDocument($document);

        self::assertInstanceOf(HtmlNormalizer::class, $subject);
    }
}
