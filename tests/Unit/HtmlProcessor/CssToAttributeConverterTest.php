<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\HtmlProcessor;

use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @covers \Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter
 *
 * @author Oliver Klee <github@oliverklee.de>
 */
class CssToAttributeConverterTest extends TestCase
{
    /**
     * @test
     */
    public function fromHtmlReturnsInstanceOfCalledClass(): void
    {
        $subject = CssToAttributeConverter::fromHtml('<html></html>');

        self::assertInstanceOf(CssToAttributeConverter::class, $subject);
    }

    /**
     * @test
     */
    public function classIsAbstractHtmlProcessor(): void
    {
        $subject = CssToAttributeConverter::fromHtml('<html></html>');

        self::assertInstanceOf(AbstractHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromDomDocumentReturnsInstanceOfCalledClass(): void
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = CssToAttributeConverter::fromDomDocument($document);

        self::assertInstanceOf(CssToAttributeConverter::class, $subject);
    }

    /**
     * @test
     */
    public function renderWithoutConvertCssToVisualAttributesCallNotAddsVisualAttributes(): void
    {
        $html = '<html style="text-align: right;"></html>';
        $subject = CssToAttributeConverter::fromHtml($html);

        self::assertContains('<html style="text-align: right;">', $subject->render());
    }

    /**
     * @test
     */
    public function convertCssToVisualAttributesUsesFluentInterface(): void
    {
        $html = '<html style="text-align: right;"></html>';
        $subject = CssToAttributeConverter::fromHtml($html);

        self::assertSame($subject, $subject->convertCssToVisualAttributes());
    }

    /**
     * @return string[][]
     */
    public function matchingCssToHtmlMappingDataProvider(): array
    {
        return [
            'background-color => bgcolor' => ['<p style="background-color: red;">hi</p>', 'bgcolor="red"'],
            'background-color with !important => bgcolor' => [
                '<p style="background-color: red !important;">hi</p>',
                'bgcolor="red"',
            ],
            'p.text-align => align' => ['<p style="text-align: left;">hi</p>', 'align="left"'],
            'div.text-align => align' => ['<div style="text-align: left;">hi</div>', 'align="left"'],
            'td.text-align => align' => [
                '<table><tr><td style="text-align: left;">hi</td></tr></table>',
                'align="left',
            ],
            'text-align: left => align=left' => ['<p style="text-align: left;">hi</p>', 'align="left"'],
            'text-align: right => align=right' => ['<p style="text-align: right;">hi</p>', 'align="right"'],
            'text-align: center => align=center' => ['<p style="text-align: center;">hi</p>', 'align="center"'],
            'text-align: justify => align:justify' => ['<p style="text-align: justify;">hi</p>', 'align="justify"'],
            'img.float: right => align=right' => ['<img style="float: right;">', 'align="right"'],
            'img.float: left => align=left' => ['<img style="float: left;">', 'align="left"'],
            'table.float: right => align=right' => ['<table style="float: right;"></table>', 'align="right"'],
            'table.float: left => align=left' => ['<table style="float: left;"></table>', 'align="left"'],
            'table.border-spacing: 0 => cellspacing=0' => [
                '<table style="border-spacing: 0;"></table>',
                'cellspacing="0"',
            ],
            'background => bgcolor' => ['<p style="background: red top;">Bonjour</p>', 'bgcolor="red"'],
            'width with px' => ['<p style="width: 100px;">Hi</p>', 'width="100"'],
            'width with %' => ['<p style="width: 50%;">Hi</p>', 'width="50%"'],
            'width with decimal %' => ['<p style="width: 50.5%;">Hi</p>', 'width="50.5%"'],
            'height with px' => ['<p style="height: 100px;">Hi</p>', 'height="100"'],
            'height with %' => ['<p style="height: 50%;">Hi</p>', 'height="50%"'],
            'height with decimal %' => ['<p style="height: 50.5%;">Hi</p>', 'height="50.5%"'],
            'img.margin: 0 auto (horizontal centering) => align=center' => [
                '<img style="margin: 0 auto;">',
                'align="center"',
            ],
            'img.margin: auto (horizontal centering) => align=center' => [
                '<img style="margin: auto;">',
                'align="center"',
            ],
            'img.margin: 10 auto 30 auto (horizontal centering) => align=center' => [
                '<img style="margin: 10 auto 30 auto;">',
                'align="center"',
            ],
            'table.margin: 0 auto (horizontal centering) => align=center' => [
                '<table style="margin: 0 auto;"></table>',
                'align="center"',
            ],
            'table.margin: auto (horizontal centering) => align=center' => [
                '<table style="margin: auto;"></table>',
                'align="center"',
            ],
            'table.margin: 10 auto 30 auto (horizontal centering) => align=center' => [
                '<table style="margin: 10 auto 30 auto;"></table>',
                'align="center"',
            ],
            'img.border: none => border=0' => ['<img style="border: none;">', 'border="0"'],
            'img.border: 0 => border=0' => ['<img style="border: none;">', 'border="0"'],
            'table.border: none => border=0' => ['<table style="border: none;"></table>', 'border="0"'],
            'table.border: 0 => border=0' => ['<table style="border: 0;"></table>', 'border="0"'],
        ];
    }

    /**
     * @test
     *
     * @param string $body The HTML
     * @param string $attributes The attributes that are expected on the element
     *
     * @dataProvider matchingCssToHtmlMappingDataProvider
     */
    public function convertCssToVisualAttributesMapsSuitableCssToHtml(string $body, string $attributes): void
    {
        $subject = CssToAttributeConverter::fromHtml('<html><body>' . $body . '</body></html>');

        $subject->convertCssToVisualAttributes();
        $html = $subject->renderBodyContent();

        self::assertContains($attributes, $html);
    }

    /**
     * @return string[][]
     */
    public function notMatchingCssToHtmlMappingDataProvider(): array
    {
        return [
            'background URL' => ['<p style="background: url(bg.png);">Hello</p>'],
            'background URL with position' => ['<p style="background: url(bg.png) top;">Hello</p>'],
            'p.margin: 10 5 30 auto (no horizontal centering)' => ['<img style="margin: 10 5 30 auto;">'],
            'p.margin: auto' => ['<p style="margin: auto;">Hi</p>'],
            'p.border: none' => ['<p style="border: none;">Hi</p>'],
            'img.border: 1px solid black' => ['<img style="border: 1px solid black;">'],
            'span.text-align' => ['<span style="text-align: justify;">Hi</span>'],
            'text-align: inherit' => ['<p style="text-align: inherit;">Hi</p>'],
            'span.float' => ['<span style="float: right;">Hi</span>'],
            'float: none' => ['<table style="float: none;"></table>'],
            'p.border-spacing' => ['<p style="border-spacing: 5px;">Hi</p>'],
            'height: auto' => ['<img src="logo.png" alt="" style="height: auto;">'],
            'width: auto' => ['<img src="logo.png" alt="" style="width: auto;">'],
        ];
    }

    /**
     * @test
     *
     * @param string $body the HTML
     *
     * @dataProvider notMatchingCssToHtmlMappingDataProvider
     */
    public function convertCssToVisualAttributesNotMapsUnsuitableCssToHtml(string $body): void
    {
        $subject = CssToAttributeConverter::fromHtml('<html><body>' . $body . '</body></html>');

        $subject->convertCssToVisualAttributes();
        $html = $subject->renderBodyContent();

        self::assertContains($body, $html);
    }
}
