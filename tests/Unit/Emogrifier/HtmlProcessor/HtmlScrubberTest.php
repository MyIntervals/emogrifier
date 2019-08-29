<?php

namespace Pelago\Tests\Unit\Emogrifier\HtmlProcessor;

use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\HtmlProcessor\HtmlScrubber;

/**
 * Test case.
 *
 * @author Oliver Klee <github@oliverklee.de>
 */
class HtmlScrubberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function fromHtmlReturnsInstanceOfCalledClass()
    {
        $subject = HtmlScrubber::fromHtml('<html></html>');

        self::assertInstanceOf(HtmlScrubber::class, $subject);
    }

    /**
     * @test
     */
    public function classIsAbstractHtmlProcessor()
    {
        $subject = HtmlScrubber::fromHtml('<html></html>');

        self::assertInstanceOf(AbstractHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromDomDocumentReturnsInstanceOfCalledClass()
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = HtmlScrubber::fromDomDocument($document);

        self::assertInstanceOf(HtmlScrubber::class, $subject);
    }

    /**
     * @test
     */
    public function removeInvisibleNodesProvidesFluentInterface()
    {
        $subject = HtmlScrubber::fromHtml('<html></html>');

        $result = $subject->removeInvisibleNodes();

        self::assertSame($subject, $result);
    }

    /**
     * @return string[][]
     */
    public function displayNoneDataProvider()
    {
        return [
            'whitespace, trailing semicolon' => ['display: none;'],
            'whitespace, no trailing semicolon' => ['display: none'],
            'no whitespace, trailing semicolon' => ['display:none;'],
            'no whitespace, no trailing semicolon' => ['display:none'],
        ];
    }

    /**
     * @test
     *
     * @param string $displayNone
     *
     * @dataProvider displayNoneDataProvider
     */
    public function removeInvisibleNodesRemovesNodesWithDisplayNone($displayNone)
    {
        $subject = HtmlScrubber::fromHtml('<html><body><div style="' . $displayNone . '"></div></body></html>');

        $subject->removeInvisibleNodes();

        self::assertNotRegExp('/display:\\s*none;?/', $subject->render());
    }
}
