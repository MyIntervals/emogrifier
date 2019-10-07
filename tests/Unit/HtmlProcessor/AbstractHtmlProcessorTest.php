<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\HtmlProcessor;

use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\Tests\Unit\HtmlProcessor\Fixtures\TestingHtmlProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <github@oliverklee.de>
 */
class AbstractHtmlProcessorTest extends TestCase
{
    /**
     * @test
     */
    public function fromHtmlReturnsAbstractHtmlProcessor()
    {
        $subject = TestingHtmlProcessor::fromHtml('<html></html>');

        self::assertInstanceOf(AbstractHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromHtmlReturnsInstanceOfCalledClass()
    {
        $subject = TestingHtmlProcessor::fromHtml('<html></html>');

        self::assertInstanceOf(TestingHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromDomDocumentReturnsAbstractHtmlProcessor()
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = TestingHtmlProcessor::fromDomDocument($document);

        self::assertInstanceOf(AbstractHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromDomDocumentReturnsInstanceOfCalledClass()
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = TestingHtmlProcessor::fromDomDocument($document);

        self::assertInstanceOf(TestingHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function renderRendersDocumentProvidedToFromDomDocument()
    {
        $innerHtml = '<p>Hello world!</p>';
        $document = new \DOMDocument();
        $document->loadHTML('<html>' . $innerHtml . '</html>');
        $subject = TestingHtmlProcessor::fromDomDocument($document);

        $html = $subject->render();

        self::assertContains($innerHtml, $html);
    }

    /**
     * @test
     */
    public function reformatsHtml()
    {
        $rawHtml = '<!DOCTYPE HTML>' .
            '<html>' .
            '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>' .
            '<body></body>' .
            '</html>';
        $formattedHtml = "<!DOCTYPE HTML>\n" .
            "<html>\n" .
            '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>' . "\n" .
            "<body></body>\n" .
            "</html>\n";

        $subject = TestingHtmlProcessor::fromHtml($rawHtml);

        self::assertSame($formattedHtml, $subject->render());
    }

    /**
     * @test
     */
    public function fromHtmlWithEmptyStringThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        TestingHtmlProcessor::fromHtml('');
    }

    /**
     * @return string[][]
     */
    public function invalidHtmlDataProvider(): array
    {
        return [
            'broken nesting gets nested' => ['<b><i></b></i>', '<b><i></i></b>'],
            'partial opening tag gets closed' => ['<b', '<b></b>'],
            'only opening tag gets closed' => ['<b>', '<b></b>'],
            'only closing tag gets removed' => ['foo</b> bar', 'foo bar'],
        ];
    }

    /**
     * @test
     *
     * @param string $input
     * @param string $expectedHtml
     *
     * @dataProvider invalidHtmlDataProvider
     */
    public function renderRepairsBrokenHtml(string $input, string $expectedHtml)
    {
        $subject = TestingHtmlProcessor::fromHtml($input);
        $result = $subject->render();

        self::assertContains($expectedHtml, $result);
    }

    /**
     * @return string[][]
     */
    public function contentWithoutHtmlTagDataProvider(): array
    {
        return [
            'doctype only' => ['<!DOCTYPE html>'],
            'body content only' => ['<p>Hello</p>'],
            'HEAD element' => ['<head></head>'],
            'BODY element' => ['<body></body>'],
            'HEAD AND BODY element' => ['<head></head><body></body>'],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     *
     * @dataProvider contentWithoutHtmlTagDataProvider
     */
    public function addsMissingHtmlTag(string $html)
    {
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        self::assertContains('<html>', $result);
    }

    /**
     * @return string[][]
     */
    public function contentWithoutHeadTagDataProvider(): array
    {
        return [
            'doctype only' => ['<!DOCTYPE html>'],
            'body content only' => ['<p>Hello</p>'],
            'BODY element' => ['<body></body>'],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     *
     * @dataProvider contentWithoutHeadTagDataProvider
     */
    public function addsMissingHeadTag(string $html)
    {
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        self::assertContains('<head>', $result);
    }

    /**
     * @return string[][]
     */
    public function contentWithoutBodyTagDataProvider(): array
    {
        return [
            'doctype only' => ['<!DOCTYPE html>'],
            'HEAD element' => ['<head></head>'],
            'body content only' => ['<p>Hello</p>'],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     *
     * @dataProvider contentWithoutBodyTagDataProvider
     */
    public function addsMissingBodyTag(string $html)
    {
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        self::assertContains('<body>', $result);
    }

    /**
     * @test
     */
    public function putsMissingBodyElementAroundBodyContent()
    {
        $subject = TestingHtmlProcessor::fromHtml('<p>Hello</p>');

        $result = $subject->render();

        self::assertContains('<body><p>Hello</p></body>', $result);
    }

    /**
     * @return string[][]
     */
    public function specialCharactersDataProvider(): array
    {
        return [
            'template markers with dollar signs & square brackets' => ['$[USER:NAME]$'],
            'UTF-8 umlauts' => ['Küss die Hand, schöne Frau. イリノイ州シカゴにて、アイルランド系の家庭に、'],
            'HTML entities' => ['a &amp; b &gt; c'],
            'curly braces' => ['{Happy new year!}'],
        ];
    }

    /**
     * @test
     *
     * @param string $codeNotToBeChanged
     *
     * @dataProvider specialCharactersDataProvider
     */
    public function keepsSpecialCharactersInTextNodes(string $codeNotToBeChanged)
    {
        $html = '<html><p>' . $codeNotToBeChanged . '</p></html>';
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        self::assertContains($codeNotToBeChanged, $result);
    }

    /**
     * @test
     */
    public function addsMissingHtml5DocumentType()
    {
        $subject = TestingHtmlProcessor::fromHtml('<html></html>');

        $result = $subject->render();

        self::assertContains('<!DOCTYPE html>', $result);
    }

    /**
     * @return string[][]
     *
     * @psalm-return array<string, array<int, string>>
     */
    public function documentTypeDataProvider(): array
    {
        return [
            'HTML5' => ['<!DOCTYPE html>'],
            'XHTML 1.0 strict' => [
                '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" ' .
                '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
            ],
            'XHTML 1.0 transitional' => [
                '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" ' .
                '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
            ],
            'HTML 4 transitional' => [
                '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" ' .
                '"http://www.w3.org/TR/REC-html40/loose.dtd">',
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $documentType
     *
     * @dataProvider documentTypeDataProvider
     */
    public function keepsExistingDocumentType(string $documentType)
    {
        $html = $documentType . '<html></html>';
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        self::assertContains($documentType, $result);
    }

    /**
     * @test
     */
    public function addsMissingContentTypeMetaTag()
    {
        $subject = TestingHtmlProcessor::fromHtml('<p>Hello</p>');

        $result = $subject->render();

        self::assertContains('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $result);
    }

    /**
     * @test
     */
    public function notAddsSecondContentTypeMetaTag()
    {
        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        $numberOfContentTypeMetaTags = \substr_count($result, 'Content-Type');
        self::assertSame(1, $numberOfContentTypeMetaTags);
    }

    /**
     * @return string[][]
     *
     * @psalm-return array<string, array{0:string, 1:string}>
     */
    public function xmlSelfClosingTagDataProvider(): array
    {
        return [
            '<br>' => ['<br/>', 'br'],
            '<wbr>' => ['foo<wbr/>bar', 'wbr'],
            '<embed>' => [
                '<embed type="video/mp4" src="https://example.com/flower.mp4" width="250" height="200"/>',
                'embed',
            ],
            '<picture> with <source> and <img>' => [
                '<picture><source srcset="https://example.com/flower-800x600.jpeg" media="(min-width: 600px)"/>'
                . '<img src="https://example.com/flower-400x300.jpeg"/></picture>',
                'source',
            ],
            '<video> with <track>' => [
                '<video controls width="250" src="https://example.com/flower.mp4">'
                . '<track default kind="captions" srclang="en" src="https://example.com/flower.vtt"/></video>',
                'track',
            ],
        ];
    }

    /**
     * @return string[][]
     *
     * @psalm-return array<string, array{0:string, 1:string}>
     */
    public function nonXmlSelfClosingTagDataProvider(): array
    {
        return \array_map(
            /**
             * @psalm-param array{0:string, 1:string} $dataset
             *
             * @psalm-return array{0:string, 1:string}
             */
            static function (array $dataset) {
                $dataset[0] = \str_replace('/>', '>', $dataset[0]);
                return $dataset;
            },
            $this->xmlSelfClosingTagDataProvider()
        );
    }

    /**
     * @return string[][] Each dataset has three elements in the following order:
     *         - HTML with non-XML self-closing tags (e.g. "...<br>...");
     *         - The equivalent HTML with XML self-closing tags (e.g. "...<br/>...");
     *         - The name of a self-closing tag contained in the HTML (e.g. "br").
     *
     * @psalm-return array<string, array{0:string, 1:string, 2:string}>
     */
    public function selfClosingTagDataProvider(): array
    {
        return \array_map(
            /**
             * @psalm-param array{0:string, 1:string} $dataset
             *
             * @psalm-return array{0:string, 1:string, 2:string}
             */
            static function (array $dataset) {
                \array_unshift($dataset, \str_replace('/>', '>', $dataset[0]));

                /** @psalm-var array{0:string, 1:string, 2:string} */
                return $dataset;
            },
            $this->xmlSelfClosingTagDataProvider()
        );
    }

    /**
     * Concatenates pairs of datasets (in a similar way to SQL `JOIN`) such that each new dataset consists of a 'row'
     * from a left-hand-side dataset joined with a 'row' from a right-hand-side dataset.
     *
     * @param string[][] $leftDatasets
     * @param string[][] $rightDatasets
     *
     * @psalm-param array<string, array<int, string>> $leftDatasets
     * @psalm-param array<string, array<int, string>> $rightDatasets
     *
     * @return string[][] The new datasets comprise the first dataset from the left-hand side with each of the datasets
     * from the right-hand side, and the each of the remaining datasets from the left-hand side with the first dataset
     * from the right-hand side.
     */
    public static function joinDatasets(array $leftDatasets, array $rightDatasets): array
    {
        $datasets = [];
        $doneFirstLeft = false;
        foreach ($leftDatasets as $leftDatasetName => $leftDataset) {
            foreach ($rightDatasets as $rightDatasetName => $rightDataset) {
                $datasets[$leftDatasetName . ' & ' . $rightDatasetName]
                    = \array_merge($leftDataset, $rightDataset);
                if ($doneFirstLeft) {
                    // Not all combinations are required,
                    // just all of 'right' with one of 'left' and all of 'left' with one of 'right'.
                    break;
                }
            }
            $doneFirstLeft = true;
        }
        return $datasets;
    }

    /**
     * @return string[][]
     */
    public function documentTypeAndSelfClosingTagDataProvider(): array
    {
        return self::joinDatasets($this->documentTypeDataProvider(), $this->selfClosingTagDataProvider());
    }

    /**
     * @test
     *
     * @param string $documentType
     * @param string $htmlWithNonXmlSelfClosingTags
     * @param string $htmlWithXmlSelfClosingTags
     *
     * @dataProvider documentTypeAndSelfClosingTagDataProvider
     */
    public function convertsXmlSelfClosingTagsToNonXmlSelfClosingTag(
        string $documentType,
        string $htmlWithNonXmlSelfClosingTags,
        string $htmlWithXmlSelfClosingTags
    ) {
        $subject = TestingHtmlProcessor::fromHtml(
            $documentType . '<html><body>' . $htmlWithXmlSelfClosingTags . '</body></html>'
        );

        $result = $subject->render();

        self::assertContains('<body>' . $htmlWithNonXmlSelfClosingTags . '</body>', $result);
    }

    /**
     * @test
     *
     * @param string $documentType
     * @param string $htmlWithNonXmlSelfClosingTags
     *
     * @dataProvider documentTypeAndSelfClosingTagDataProvider
     */
    public function keepsNonXmlSelfClosingTags(string $documentType, string $htmlWithNonXmlSelfClosingTags)
    {
        $subject = TestingHtmlProcessor::fromHtml(
            $documentType . '<html><body>' . $htmlWithNonXmlSelfClosingTags . '</body></html>'
        );

        $result = $subject->render();

        self::assertContains('<body>' . $htmlWithNonXmlSelfClosingTags . '</body>', $result);
    }

    /**
     * @test
     *
     * @param string $htmlWithNonXmlSelfClosingTags
     * @param string $tagName
     *
     * @dataProvider nonXmlSelfClosingTagDataProvider
     */
    public function notAddsClosingTagForSelfClosingTags(string $htmlWithNonXmlSelfClosingTags, string $tagName)
    {
        $subject = TestingHtmlProcessor::fromHtml(
            '<html><body>' . $htmlWithNonXmlSelfClosingTags . '</body></html>'
        );

        $result = $subject->render();

        self::assertNotContains('</' . $tagName, $result);
    }

    /**
     * @test
     */
    public function renderBodyContentForEmptyBodyReturnsEmptyString()
    {
        $subject = TestingHtmlProcessor::fromHtml('<html><body></body></html>');

        $result = $subject->renderBodyContent();

        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function renderBodyContentReturnsBodyContent()
    {
        $bodyContent = '<p>Hello world</p>';
        $subject = TestingHtmlProcessor::fromHtml('<html><body>' . $bodyContent . '</body></html>');

        $result = $subject->renderBodyContent();

        self::assertSame($bodyContent, $result);
    }

    /**
     * Issue #677
     *
     * @test
     */
    public function renderBodyContentForBodyWithAttributeReturnsBodyContent()
    {
        $bodyContent = '<div>simple</div>';
        $subject = TestingHtmlProcessor::fromHtml('<html><body class="foo">' . $bodyContent . '</body></html>');

        $result = $subject->renderBodyContent();

        self::assertSame($bodyContent, $result);
    }

    /**
     * @test
     *
     * @param string $codeNotToBeChanged
     *
     * @dataProvider specialCharactersDataProvider
     */
    public function renderBodyContentKeepsSpecialCharactersInTextNodes(string $codeNotToBeChanged)
    {
        $html = '<html><p>' . $codeNotToBeChanged . '</p></html>';
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->renderBodyContent();

        self::assertContains($codeNotToBeChanged, $result);
    }

    /**
     * @test
     *
     * @param string $htmlWithNonXmlSelfClosingTags
     * @param string $tagName
     *
     * @dataProvider nonXmlSelfClosingTagDataProvider
     */
    public function renderBodyContentNotAddsClosingTagForSelfClosingTags(
        string $htmlWithNonXmlSelfClosingTags,
        string $tagName
    ) {
        $subject = TestingHtmlProcessor::fromHtml(
            '<html><body>' . $htmlWithNonXmlSelfClosingTags . '</body></html>'
        );

        $result = $subject->renderBodyContent();

        self::assertNotContains('</' . $tagName, $result);
    }

    /**
     * @test
     */
    public function getDomDocumentReturnsDomDocument()
    {
        $subject = TestingHtmlProcessor::fromHtml('<html></html>');

        self::assertInstanceOf(\DOMDocument::class, $subject->getDomDocument());
    }

    /**
     * @test
     */
    public function getDomDocumentReturnsDomDocumentProvidedToFromDomDocument()
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = TestingHtmlProcessor::fromDomDocument($document);

        self::assertSame($document, $subject->getDomDocument());
    }

    /**
     * @test
     */
    public function getDomDocumentWithNormalizedHtmlRepresentsTheGivenHtml()
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>" .
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' .
            "</head>\n<body>\n<br>\n</body>\n</html>\n";
        $subject = TestingHtmlProcessor::fromHtml($html);

        $domDocument = $subject->getDomDocument();

        self::assertSame($html, $domDocument->saveHTML());
    }

    /**
     * @test
     *
     * @param string $htmlWithNonXmlSelfClosingTags
     * @param string $tagName
     *
     * @dataProvider nonXmlSelfClosingTagDataProvider
     */
    public function getDomDocumentVoidElementNotHasChildNodes(string $htmlWithNonXmlSelfClosingTags, string $tagName)
    {
        // Append a 'trap' element that might become a child node if the HTML is parsed incorrectly
        $subject = TestingHtmlProcessor::fromHtml(
            '<html><body>' . $htmlWithNonXmlSelfClosingTags . '<span>foo</span></body></html>'
        );

        $domDocument = $subject->getDomDocument();

        $voidElements = $domDocument->getElementsByTagName($tagName);
        /** @var \DOMElement $element */
        foreach ($voidElements as $element) {
            self::assertFalse($element->hasChildNodes());
        }
    }
}
