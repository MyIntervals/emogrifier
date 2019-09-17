<?php

namespace Pelago\Tests\Unit\Emogrifier\HtmlProcessor;

use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <github@oliverklee.de>
 */
class HtmlPrunerTest extends TestCase
{
    /**
     * @test
     */
    public function fromHtmlReturnsInstanceOfCalledClass()
    {
        $subject = HtmlPruner::fromHtml('<html></html>');

        self::assertInstanceOf(HtmlPruner::class, $subject);
    }

    /**
     * @test
     */
    public function classIsAbstractHtmlProcessor()
    {
        $subject = HtmlPruner::fromHtml('<html></html>');

        self::assertInstanceOf(AbstractHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromDomDocumentReturnsInstanceOfCalledClass()
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = HtmlPruner::fromDomDocument($document);

        self::assertInstanceOf(HtmlPruner::class, $subject);
    }

    /**
     * @test
     */
    public function removeElementsWithDisplayNoneProvidesFluentInterface()
    {
        $subject = HtmlPruner::fromHtml('<html></html>');

        $result = $subject->removeElementsWithDisplayNone();

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
    public function removeElementsWithDisplayNoneRemovesElementsWithDisplayNone($displayNone)
    {
        $subject = HtmlPruner::fromHtml('<html><body><div style="' . $displayNone . '"></div></body></html>');

        $subject->removeElementsWithDisplayNone();

        self::assertNotContains('<div', $subject->render());
    }
}
