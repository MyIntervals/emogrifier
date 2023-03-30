<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\HtmlProcessor;

use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\Tests\Unit\HtmlProcessor\Fixtures\TestingHtmlProcessor;
use PHPUnit\Framework\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @covers \Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor
 */
final class AbstractHtmlProcessorTest extends TestCase
{
    /**
     * @test
     */
    public function fromHtmlReturnsAbstractHtmlProcessor(): void
    {
        $subject = TestingHtmlProcessor::fromHtml('<html></html>');

        self::assertInstanceOf(AbstractHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromHtmlReturnsInstanceOfCalledClass(): void
    {
        $subject = TestingHtmlProcessor::fromHtml('<html></html>');

        self::assertInstanceOf(TestingHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromDomDocumentReturnsAbstractHtmlProcessor(): void
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = TestingHtmlProcessor::fromDomDocument($document);

        self::assertInstanceOf(AbstractHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromDomDocumentReturnsInstanceOfCalledClass(): void
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = TestingHtmlProcessor::fromDomDocument($document);

        self::assertInstanceOf(TestingHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function renderRendersDocumentProvidedToFromDomDocument(): void
    {
        $innerHtml = '<p>Hello world!</p>';
        $document = new \DOMDocument();
        $document->loadHTML('<html>' . $innerHtml . '</html>');
        $subject = TestingHtmlProcessor::fromDomDocument($document);

        $html = $subject->render();

        self::assertStringContainsString($innerHtml, $html);
    }

    /**
     * @test
     */
    public function renderPreservesBodyContentProvidedToFromHtml(): void
    {
        $innerHtml = '<p>Hello world!</p>';
        $subject = TestingHtmlProcessor::fromHtml('<html>' . $innerHtml . '</html>');

        $html = $subject->render();

        self::assertStringContainsString($innerHtml, $html);
    }

    /**
     * @test
     */
    public function renderPreservesOuterHtmlProvidedToFromHtml(): void
    {
        $rawHtml = '<!DOCTYPE HTML>' .
            '<html>' .
            '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>' .
            '<body></body>' .
            '</html>';
        $formattedHtml = "<!DOCTYPE html>\n" .
            "<html>\n" .
            '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>' . "\n" .
            "<body></body>\n" .
            "</html>\n";

        $subject = TestingHtmlProcessor::fromHtml($rawHtml);
        $html = $subject->render();

        self::assertEqualsHtml($formattedHtml, $html);
    }

    /**
     * @test
     */
    public function renderPreservesOriginalHtmlFormatting(): void
    {
        $rawHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>Hello world</title>
    </head>
    <body>
       <li><em>Hello</em> world</li>
    </body>
</html>
HTML;

        $subject = TestingHtmlProcessor::fromHtml($rawHtml);
        $renderedHtml = $subject->render();

        self::assertEqualsHtml($rawHtml, $renderedHtml);
    }

    /**
     * @test
     */
    public function fromHtmlWithEmptyStringThrowsException(): void
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
    public function renderRepairsBrokenHtml(string $input, string $expectedHtml): void
    {
        $subject = TestingHtmlProcessor::fromHtml($input);
        $result = $subject->render();

        self::assertStringContainsString($expectedHtml, $result);
    }

    /**
     * @return string[][]
     */
    public function provideHtmlWithOptionalTagsOmitted(): array
    {
        return [
            'LI end tag omission with LI element following' => [
                '<ul><li> One <li> Two </li></ul>',
                '<ul><li> One </li><li> Two </li></ul>',
            ],
            'LI end tag omission at end of list' => [
                '<ul><li> One </li><li> Two </ul>',
                '<ul><li> One </li><li> Two </li></ul>',
            ],
            // broken: DT end tag omission with DT element following
            'DT end tag omission with DD element following' => [
                '<dl><dt> One </dt><dt> Two <dd> Buckle My Shoe </dd></dl>',
                '<dl><dt> One </dt><dt> Two </dt><dd> Buckle My Shoe </dd></dl>',
            ],
            // broken: DD end tag omission with DD element following
            'DD end tag omission with DT element following' => [
                '<dl><dt> One </dt><dd> A </dd><dd> B <dt> Two </dt><dd> C </dd></dl>',
                '<dl><dt> One </dt><dd> A </dd><dd> B </dd><dt> Two </dt><dd> C </dd></dl>',
            ],
            'DD end tag omission at end of list' => [
                '<dl><dt> One </dt><dd> A </dd><dd> B </dd><dt> Two </dt><dd> C </dl>',
                '<dl><dt> One </dt><dd> A </dd><dd> B </dd><dt> Two </dt><dd> C </dd></dl>',
            ],
            // broken: RT end tag omission with RT element following
            // broken: RT end tag omission with RP element following
            'RT end tag omission at end of annotation' => [
                '<ruby> 攻殻 <rt> こうかく </rt> 機動隊 <rt> きどうたい </ruby>',
                '<ruby> 攻殻 <rt> こうかく </rt> 機動隊 <rt> きどうたい </rt></ruby>',
            ],
            // broken: RP end tag omission with RT element following
            // broken: RP end tag omission with RP element following
            'RP end tag omission at end of annotation' => [
                '<ruby> 明日 <rp> ( </rp><rt> Ashita </rt><rp> ) </ruby>',
                '<ruby> 明日 <rp> ( </rp><rt> Ashita </rt><rp> ) </rp></ruby>',
            ],
            // broken: OPTGROUP end tag omission with OPTGROUP element following
            'OPTGROUP end tag omission at end of list' => [
                '<select><optgroup><option> 1 </option><option> 2 </option></optgroup>'
                    . '<optgroup><option> A </option><option> B </option></select>',
                '<select><optgroup><option> 1 </option><option> 2 </option></optgroup>'
                    . '<optgroup><option> A </option><option> B </option></optgroup></select>',
            ],
            'OPTION end tag omission with OPTION element following' => [
                '<select><option> 1 <option> 2 </option></select>',
                '<select><option> 1 </option><option> 2 </option></select>',
            ],
            // broken: OPTION end tag omission with OPTGROUP element following
            'OPTION end tag omission at end of list' => [
                '<select><option> 1 </option><option> 2 </select>',
                '<select><option> 1 </option><option> 2 </option></select>',
            ],
            // broken: COLGROUP start tag omission
            'COLGROUP end tag omission' => [
                '<table><colgroup><col><tr><td></td></tr></table>',
                '<table><colgroup><col></colgroup><tr><td></td></tr></table>',
            ],
            'CAPTION end tag omission' => [
                '<table><caption> Caption <tr><td></td></tr></table>',
                '<table><caption> Caption </caption><tr><td></td></tr></table>',
            ],
            'THEAD end tag omission with TBODY element following' => [
                '<table><thead><tr><td></td></tr><tbody><tr><td></td></tr></tbody></table>',
                '<table><thead><tr><td></td></tr></thead><tbody><tr><td></td></tr></tbody></table>',
            ],
            'THEAD end tag omission with TFOOT element following' => [
                '<table><thead><tr><td></td></tr><tfoot><tr><td></td></tr></tfoot></table>',
                '<table><thead><tr><td></td></tr></thead><tfoot><tr><td></td></tr></tfoot></table>',
            ],
            // broken: TBODY start tag omission
            'TBODY end tag omission with TBODY element following' => [
                '<table><tbody><tr><td></td></tr><tbody><tr><td></td></tr></tbody></table>',
                '<table><tbody><tr><td></td></tr></tbody><tbody><tr><td></td></tr></tbody></table>',
            ],
            'TBODY end tag omission with TFOOT element following' => [
                '<table><tbody><tr><td></td></tr><tfoot><tr><td></td></tr></tfoot></table>',
                '<table><tbody><tr><td></td></tr></tbody><tfoot><tr><td></td></tr></tfoot></table>',
            ],
            'TR end tag omission with TR element following' => [
                '<table><tr><td></td><tr><td></td></tr></table>',
                '<table><tr><td></td></tr><tr><td></td></tr></table>',
            ],
            'TD end tag omission with TD element following' => [
                '<table><tr><td><td></td></tr></table>',
                '<table><tr><td></td><td></td></tr></table>',
            ],
            'TD end tag omission with TH element following' => [
                '<table><tr><td><th></th></tr></table>',
                '<table><tr><td></td><th></th></tr></table>',
            ],
            'TH end tag omission with TD element following' => [
                '<table><tr><th><td></td></tr></table>',
                '<table><tr><th></th><td></td></tr></table>',
            ],
            'TH end tag omission with TH element following' => [
                '<table><tr><th><th></th></tr></table>',
                '<table><tr><th></th><th></th></tr></table>',
            ],
            'P end tag omission with HR element following' => [
                '<p> Hello <hr>',
                '<p> Hello </p><hr>',
            ],
        ];
    }

    /**
     * @test
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#optional-tags
     *
     * @param string $htmlWithOptionalTagsOmitted
     * @param string $equivalentHtml
     *
     * @dataProvider provideHtmlWithOptionalTagsOmitted
     */
    public function insertsOptionallyOmittedTags(string $htmlWithOptionalTagsOmitted, string $equivalentHtml): void
    {
        $subject = TestingHtmlProcessor::fromHtml('<body>' . $htmlWithOptionalTagsOmitted . '</body>');

        $result = $subject->render();

        self::assertContainsHtml('<body>' . $equivalentHtml . '</body>', $result);
    }

    /**
     * @return string[][]
     */
    public function providePSiblingTagName(): array
    {
        return [
            ['address'],
            // broken: article
            // broken: aside
            ['blockquote'],
            // broken: details
            ['div'],
            ['dl'],
            ['fieldset'],
            // broken: figcaption
            // broken: figure
            // broken: footer
            ['form'],
            ['h1'],
            ['h2'],
            ['h3'],
            ['h4'],
            ['h5'],
            ['h6'],
            // broken: header
            // broken: hgroup
            // broken: main
            ['menu'],
            // broken: nav
            ['ol'],
            ['p'],
            ['pre'],
            // broken: section
            ['table'],
            ['ul'],
        ];
    }

    /**
     * @test
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#optional-tags
     *
     * @param string $siblingTagName
     *
     * @dataProvider providePSiblingTagName
     */
    public function insertsOptionallyOmittedClosingPTagBeforeSibling(string $siblingTagName): void
    {
        $subject = TestingHtmlProcessor::fromHtml(
            '<body><p> Hello <' . $siblingTagName . '></' . $siblingTagName . '></body>'
        );

        $result = $subject->render();

        self::assertContainsHtml(
            '<body><p> Hello </p><' . $siblingTagName . '></' . $siblingTagName . '></body>',
            $result
        );
    }

    /**
     * @return string[][]
     */
    public function providePParentTagName(): array
    {
        return [
            ['address'],
            ['article'],
            ['aside'],
            ['blockquote'],
            ['div'],
            ['fieldset'],
            ['figure'],
            ['footer'],
            ['form'],
            ['header'],
            ['main'],
            ['nav'],
            ['section'],
            ['template'],
        ];
    }

    /**
     * @test
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#optional-tags
     *
     * @param string $parentTagName
     *
     * @dataProvider providePParentTagName
     */
    public function insertsOptionallyOmittedClosingPTagAtEndOfParent(string $parentTagName): void
    {
        $subject = TestingHtmlProcessor::fromHtml(
            '<body><' . $parentTagName . '><p> Hello </' . $parentTagName . '><p> World </p></body>'
        );

        $result = $subject->render();

        self::assertContainsHtml(
            '<body><' . $parentTagName . '><p> Hello </p></' . $parentTagName . '><p> World </p></body>',
            $result
        );
    }

    /**
     * @return string[][]
     */
    public function provideContentWithoutHtmlTag(): array
    {
        return [
            'doctype only' => ['<!DOCTYPE html>'],
            'body content only' => ['<p>Hello</p>'],
            'HEAD element' => ['<head></head>'],
            'BODY element' => ['<body></body>'],
            'HEAD AND BODY element' => ['<head></head><body></body>'],
            'META element with Content-Type as a value' => ['<meta name="description" content="Content-Type">'],
            'HEAD element with Content-Type in comment' => ['<head><!-- Content-Type --></head>'],
            'BODY element with Content-Type in text' => ['<body>Content-Type</body>'],
            'body content only with Content-Type in text' => ['<p>Content-Type</p>'],
            'BODY element containing Content-Type META tag'
                => ['<body><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></body>'],
            'body content only with Content-Type META tag'
                => ['<p>hello</p><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     *
     * @dataProvider provideContentWithoutHtmlTag
     */
    public function addsMissingHtmlTag(string $html): void
    {
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        self::assertStringContainsString('<html>', $result);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideContentWithHtmlTag(): array
    {
        return [
            'HTML only' => ['<html></html>'],
            'HTML start tag only' => ['<html>'],
            'doctype and HTML only' => ['<!DOCTYPE html><html></html>'],
            'HTML and body content only' => ['<html><p>Hello</p></html>'],
            'HTML and HEAD element' => ['<html><head></head></html>'],
            'HTML and BODY element' => ['<html><body></body></html>'],
            'HTML, HEAD AND BODY element' => ['<html><head></head><body></body></html>'],
        ];
    }

    /**
     * @return array<string, array<int, string>> The second element of each dataset is optional, and is the expected
     * normalization of the `<html>` tag, if different.
     */
    public function provideHtmlTagWithAttributes(): array
    {
        return [
            'with one attribute' => ['<html lang="de">'],
            'with two attributes' => ['<html lang="de" dir="ltr">'],
            'with line feeds within tag' => ["<html\nlang='de'\n>", '<html lang="de">'],
            'with Windows line endings within tag' => ["<html\r\nlang='de'\r\n>", '<html lang="de">'],
            'with TABs within tag' => ["<html\tlang='de'\t>", '<html lang="de">'],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     *
     * @dataProvider provideContentWithHtmlTag
     * @dataProvider provideHtmlTagWithAttributes
     */
    public function notAddsSecondHtmlTag(string $html): void
    {
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        $htmlTagCount = \preg_match_all('%<html[\\s/>]%', $result);
        self::assertSame(1, $htmlTagCount);
    }

    /**
     * @test
     *
     * @param string $html
     * @param ?string $normalizedHtmlTag
     *
     * @dataProvider provideHtmlTagWithAttributes
     */
    public function preservesHtmlTagAttributes(string $html, ?string $normalizedHtmlTag = null): void
    {
        if ($normalizedHtmlTag === null) {
            $normalizedHtmlTag = $html;
        }
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        self::assertStringContainsString($normalizedHtmlTag, $result);
    }

    /**
     * @return string[][]
     */
    public function provideContentWithoutHeadTag(): array
    {
        return [
            'doctype only' => ['<!DOCTYPE html>'],
            'HTML element' => ['<html></html>'],
            'body content only' => ['<p>Hello</p>'],
            'BODY element' => ['<body></body>'],
            'HEADER element' => ['<header></header>'],
            'http-equiv META element (implicit HEAD)'
                => ['<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'],
            'viewport META element (implicit HEAD)'
                => ['<meta name="viewport" content="width=device-width, initial-scale=1.0">'],
            'META element with Content-Type as a value' => ['<meta name="description" content="Content-Type">'],
            'BODY element with Content-Type in text' => ['<body>Content-Type</body>'],
            'body content only with Content-Type in text' => ['<p>Content-Type</p>'],
            'http-equiv META element within BODY (not allowed)'
                => ['<body><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></body>'],
            'http-equiv META element after P (implicit BODY, not allowed)'
                => ['<p>hello</p><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'],
            'http-equiv META element within P (not allowed)'
                => ['<p><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></p>'],
            'viewport META element within P (allowed)'
                => ['<p><meta name="viewport" content="width=device-width, initial-scale=1.0"></p>'],
            'http-equiv META element within HEADER (not allowed)'
                => ['<header><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></header>'],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     *
     * @dataProvider provideContentWithoutHeadTag
     * @dataProvider provideHtmlTagWithAttributes
     */
    public function addsMissingHeadTagExactlyOnce(string $html): void
    {
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        $headTagCount = \preg_match_all('%<head[\\s/>]%', $result);
        self::assertSame(1, $headTagCount);
    }

    /**
     * @return string[][]
     */
    public function provideContentWithHeadTag(): array
    {
        return [
            'HEAD element' => ['<head></head>'],
            'HEAD element, capitalized' => ['<HEAD></HEAD>'],
            '(invalid) void HEAD element' => ['<head/>'],
            'HEAD element with attribute' => ['<head lang="en"></head>'],
            'HEAD element and HEADER element' => ['<head></head><header></header>'],
            'HEAD element with Content-Type in comment' => ['<head><!-- Content-Type --></head>'],
            'HEAD element with Content-Type as META value'
                => ['<head><meta name="description" content="Content-Type"></head>'],
            'with BODY element with Content-Type in text' => ['<head></head><body>Content-Type</body>'],
            'with implicit body content with Content-Type in text' => ['<head></head><p>Content-Type</p>'],
            'with BODY element containing Content-Type META tag'
                => ['<head></head><body><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></body>'],
            'with implicit body content with Content-Type META tag'
                => ['<head></head><p>hello</p><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'],
            'with end tag omitted and BODY element containing Content-Type META tag'
                => ['<head><body><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></body>'],
            'with end tag omitted and implicit body content with Content-Type META tag'
                => ['<head><p>hello</p><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'],
            'with Content-Type META tag after end tag'
                => ['<head></head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'],
            'with Content-Type META tag after uppercase end tag'
                => ['<HEAD></HEAD><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     *
     * @dataProvider provideContentWithHeadTag
     */
    public function notAddsSecondHeadTag(string $html): void
    {
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        $headTagCount = \preg_match_all('%<head[\\s/>]%', $result);
        self::assertSame(1, $headTagCount);
    }

    /**
     * @test
     */
    public function preservesHeadAttributes(): void
    {
        $subject = TestingHtmlProcessor::fromHtml('<head lang="en"></head>');

        $result = $subject->render();

        self::assertStringContainsString('<head lang="en">', $result);
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
    public function addsMissingBodyTag(string $html): void
    {
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        self::assertStringContainsString('<body>', $result);
    }

    /**
     * @test
     */
    public function putsMissingBodyElementAroundBodyContent(): void
    {
        $subject = TestingHtmlProcessor::fromHtml('<p>Hello</p>');

        $result = $subject->render();

        self::assertStringContainsString('<body><p>Hello</p></body>', $result);
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
    public function keepsSpecialCharactersInTextNodes(string $codeNotToBeChanged): void
    {
        $html = '<html><p>' . $codeNotToBeChanged . '</p></html>';
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        self::assertStringContainsString($codeNotToBeChanged, $result);
    }

    /**
     * @test
     */
    public function addsMissingHtml5DocumentType(): void
    {
        $subject = TestingHtmlProcessor::fromHtml('<html></html>');

        $result = $subject->render();

        self::assertStringContainsString('<!DOCTYPE html>', $result);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function documentTypeDataProvider(): array
    {
        return [
            'HTML5' => ['<!DOCTYPE html>'],
            'HTML 4.01 strict' => [
                '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" ' .
                '"http://www.w3.org/TR/html4/strict.dtd">',
            ],
            'HTML 4.01 transitional' => [
                '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" ' .
                '"http://www.w3.org/TR/html4/loose.dtd">',
            ],
            'HTML 4 transitional' => [
                '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" ' .
                '"http://www.w3.org/TR/REC-html40/loose.dtd">',
            ],
            'HTML 3.2' => ['<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">'],
        ];
    }

    /**
     * @test
     *
     * @param string $documentType
     *
     * @dataProvider documentTypeDataProvider
     */
    public function keepsExistingDocumentType(string $documentType): void
    {
        $html = $documentType . '<html></html>';
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        self::assertStringContainsString($documentType, $result);
    }

    /**
     * @return string[][]
     */
    public function normalizedDocumentTypeDataProvider(): array
    {
        return [
            'HTML5, uppercase' => ['<!DOCTYPE HTML>', '<!DOCTYPE html>'],
            'HTML5, lowercase' => ['<!doctype html>', '<!DOCTYPE html>'],
            'HTML5, mixed case' => ['<!DocType Html>', '<!DOCTYPE html>'],
            'HTML5, extra whitespace' => ['<!DOCTYPE  html  >', '<!DOCTYPE html>'],
            'HTML 4 transitional, uppercase' => [
                '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" '
                    . '"http://www.w3.org/TR/REC-html40/loose.dtd">',
                '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" '
                    . '"http://www.w3.org/TR/REC-html40/loose.dtd">',
            ],
            'HTML 4 transitional, lowercase' => [
                '<!doctype html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" '
                    . '"http://www.w3.org/TR/REC-html40/loose.dtd">',
                '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" '
                    . '"http://www.w3.org/TR/REC-html40/loose.dtd">',
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $documentType
     * @param string $normalizedDocumentType
     *
     * @dataProvider normalizedDocumentTypeDataProvider
     */
    public function normalizesDocumentType(string $documentType, string $normalizedDocumentType): void
    {
        $html = $documentType . '<html></html>';
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        self::assertStringContainsString($normalizedDocumentType, $result);
    }

    /**
     * @return string[][]
     */
    public function provideMalformedContentTypeMetaTag(): array
    {
        return [
            'extra character before META' => ['<xmeta http-equiv="Content-Type" content="text/html; charset=utf-8">'],
            'extra character after META' => ['<metax http-equiv="Content-Type" content="text/html; charset=utf-8">'],
            'extra character before HTTP-EQUIV'
                => ['<meta xhttp-equiv="Content-Type" content="text/html; charset=utf-8">'],
            'extra character after HTTP-EQUIV'
                => ['<meta http-equivx="Content-Type" content="text/html; charset=utf-8">'],
            'extra character before CONTENT-TYPE'
                => ['<meta http-equiv=xContent-Type content="text/html; charset=utf-8">'],
            'extra character after CONTENT-TYPE'
                => ['<meta http-equiv=Content-Typex content="text/html; charset=utf-8">'],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     *
     * @dataProvider provideContentWithoutHeadTag
     * @dataProvider provideContentWithHeadTag
     * @dataProvider provideMalformedContentTypeMetaTag
     */
    public function addsMissingContentTypeMetaTagOnlyOnce(string $html): void
    {
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->render();

        $headEndPosition = \stripos($result, '</head>');
        $resultBeforeHeadEnd = $headEndPosition !== false ? \substr($result, 0, $headEndPosition) : $result;
        // PHP DOM does not understand `<header>` element so does not know it would implicitly start `<body>`.
        $headerStartPosition = \stripos($resultBeforeHeadEnd, '<header');
        if ($headerStartPosition !== false) {
            $resultBeforeHeadEnd = \substr($resultBeforeHeadEnd, 0, $headerStartPosition);
        }

        $numberOfContentTypeMetaTags = \substr_count(
            $resultBeforeHeadEnd,
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
        );
        self::assertSame(1, $numberOfContentTypeMetaTags);
    }

    /**
     * @return string[][]
     */
    public function provideContentTypeMetaTag(): array
    {
        return [
            'double-quoted attribute values' => ['<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'],
            'single-quoted attribute values'
                => ['<meta http-equiv=\'Content-Type\' content=\'text/html; charset=utf-8\'>'],
            'unquoted attribute values' => ['<meta http-equiv=Content-Type content=text/html;charset=utf-8>'],
            'reverse order attributes' => ['<meta content="text/html; charset=utf-8" http-equiv="Content-Type">'],
            'without charset' => ['<meta http-equiv="Content-Type" content="text/html">'],
            'XHTML' => ['<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8">'],
            'tag with self-closing slash' => ['<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'],
            'tag with extra whitespace' => ['<meta  http-equiv="Content-Type"  content="text/html ;  charset=utf-8" >'],
            'tag with newlines' => ["<meta\nhttp-equiv='Content-Type'\ncontent='text/html\n;\ncharset=utf-8'\n>"],
            'uppercase tag name' => ['<META http-equiv="Content-Type" content="text/html; charset=utf-8">'],
            'uppercase attribute names' => ['<meta HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">'],
            'uppercase `Content-Type`' => ['<meta http-equiv="CONTENT-TYPE" content="text/html; charset=utf-8">'],
            'lowercase `Content-Type`' => ['<meta http-equiv="content-type" content="text/html; charset=utf-8">'],
            'uppercase MIME type' => ['<meta http-equiv="Content-Type" content="TEXT/HTML; charset=utf-8">'],
            'uppercase `charset`' => ['<meta http-equiv="Content-Type" content="text/html; CHARSET=utf-8">'],
            'uppercase `charset` value' => ['<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'],
        ];
    }

    /**
     * @return string[][]
     */
    public function provideHtmlAroundContentType(): array
    {
        return [
            'HTML and HEAD element' => ['<html><head>', '</head></html>'],
            'HTML and HEAD element, HTML end tag omitted' => ['<html><head>', '</head>'],
            'HEAD element only' => ['<head>', '</head>'],
            'HEAD element with space after start tag' => ['<head> ', '</head>'],
            'HEAD element with line feed after start tag' => ["<head>\n", '</head>'],
            'HEAD element with Windows line ending after start tag' => ["<head>\r\n", '</head>'],
            'HEAD element with TAB after start tag' => ["<head>\t", '</head>'],
            'HEAD element with attribute' => ['<head lang="en">', '</head>'],
            'HTML, HEAD, and BODY with HEADER elements'
                => ['<html><head>', '</head><body><header></header></body></html>'],
            'HEAD element with comment' => ['<head><!--Test-->', '</head>'],
            'HEAD element with commented-out BODY start tag' => ['<head><!--<body>-->', '</head>'],
            'HEAD element with BASE element' => ['<head><base href="https://example.com"/>', '</head>'],
            'HEAD element with COMMAND element' => ['<head><command type="command"/>', '</head>'],
            'HEAD element with LINK element'
                => ['<head><link rel="stylesheet" href="https://example.org/css.css"/>', '</head>'],
            'HEAD element with another META element' => ['<head><meta name="title" content="Test"/>', '</head>'],
            'HEAD element with NOSCRIPT element'
                => ['<head><noscript><style>p{color:green}</style></noscript>', '</head>'],
            'HEAD element with SCRIPT element' => ['<head><script>console.log("Test");</script>', '</head>'],
            'HEAD element with STYLE element' => ['<head><style>p{color:green}</style>', '</head>'],
            'HEAD element with TEMPLATE element'
                => ['<head><template id="test"><p>Test</p></template></title>', '</head>'],
            'HEAD element with TITLE element' => ['<head><title>Test</title>', '</head>'],
            'HEAD element with uppercase TEMPLATE element'
                => ['<head><TEMPLATE id="test"><p>Test</p></TEMPLATE></title>', '</head>'],
            'HEAD element with uppercase TITLE element' => ['<head><TITLE>Test</TITLE>', '</head>'],
            'Second valid(ish) Content-Type in BODY' => [
                '<head>',
                '</head><body><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></body>',
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideContentTypeTagAndSurroundingHtml(): array
    {
        /** @var array<string, array<int, string>> $datasets */
        $datasets = DataProviders::cross($this->provideContentTypeMetaTag(), $this->provideHtmlAroundContentType());

        return $datasets;
    }

    /**
     * @test
     *
     * @param string $contentTypeTag
     * @param string $htmlBefore
     * @param string $htmlAfter
     *
     * @dataProvider provideContentTypeTagAndSurroundingHtml
     */
    public function notAddsSecondContentTypeMetaTag(string $contentTypeTag, string $htmlBefore, string $htmlAfter): void
    {
        $html = $htmlBefore . $contentTypeTag . $htmlAfter;
        $subject = TestingHtmlProcessor::fromHtml($html);

        $domDocument = $subject->getDomDocument();
        $resultHeadContent = $domDocument->saveHTML($domDocument->getElementsByTagName('head')->item(0));

        $numberOfContentTypeMetaTags = \substr_count(\strtolower($resultHeadContent), 'content-type');
        self::assertSame(1, $numberOfContentTypeMetaTags);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
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
     * @return array<string, array{0:string, 1:string}>
     */
    public function nonXmlSelfClosingTagDataProvider(): array
    {
        return \array_map(
            /**
             * @param array{0:string, 1:string} $dataset
             *
             * @return array{0:string, 1:string}
             */
            static function (array $dataset) {
                $dataset[0] = \str_replace('/>', '>', $dataset[0]);
                return $dataset;
            },
            $this->xmlSelfClosingTagDataProvider()
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     *         Each dataset has three elements in the following order:
     *         - HTML with non-XML self-closing tags (e.g. "...<br>...");
     *         - The equivalent HTML with XML self-closing tags (e.g. "...<br/>...");
     *         - The name of a self-closing tag contained in the HTML (e.g. "br").
     */
    public function selfClosingTagDataProvider(): array
    {
        return \array_map(
            /**
             * @param array{0: string, 1: string} $dataset
             *
             * @return array{0: string, 1: string, 2:string}
             */
            static function (array $dataset): array {
                $updatedDataset = $dataset;
                \array_unshift($updatedDataset, \str_replace('/>', '>', $dataset[0]));

                /** @var array{0: string, 1: string, 2:string} $updatedDataset */
                return $updatedDataset;
            },
            $this->xmlSelfClosingTagDataProvider()
        );
    }

    /**
     * Concatenates pairs of datasets (in a similar way to SQL `JOIN`) such that each new dataset consists of a 'row'
     * from a left-hand-side dataset joined with a 'row' from a right-hand-side dataset.
     *
     * @param array<string, array<int, string>> $leftDatasets
     * @param array<string, array<int, string>> $rightDatasets
     *
     * @return string[][] The new datasets comprise the first dataset from the left-hand side with each of the datasets
     *         from the right-hand side, and the each of the remaining datasets from the left-hand side with the first
     *         dataset from the right-hand side.
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
    ): void {
        $subject = TestingHtmlProcessor::fromHtml(
            $documentType . '<html><body>' . $htmlWithXmlSelfClosingTags . '</body></html>'
        );

        $result = $subject->render();

        self::assertStringContainsString('<body>' . $htmlWithNonXmlSelfClosingTags . '</body>', $result);
    }

    /**
     * @test
     *
     * @param string $documentType
     * @param string $htmlWithNonXmlSelfClosingTags
     *
     * @dataProvider documentTypeAndSelfClosingTagDataProvider
     */
    public function keepsNonXmlSelfClosingTags(string $documentType, string $htmlWithNonXmlSelfClosingTags): void
    {
        $subject = TestingHtmlProcessor::fromHtml(
            $documentType . '<html><body>' . $htmlWithNonXmlSelfClosingTags . '</body></html>'
        );

        $result = $subject->render();

        self::assertStringContainsString('<body>' . $htmlWithNonXmlSelfClosingTags . '</body>', $result);
    }

    /**
     * @test
     *
     * @param string $htmlWithNonXmlSelfClosingTags
     * @param string $tagName
     *
     * @dataProvider nonXmlSelfClosingTagDataProvider
     */
    public function notAddsClosingTagForSelfClosingTags(string $htmlWithNonXmlSelfClosingTags, string $tagName): void
    {
        $subject = TestingHtmlProcessor::fromHtml(
            '<html><body>' . $htmlWithNonXmlSelfClosingTags . '</body></html>'
        );

        $result = $subject->render();

        self::assertStringNotContainsString('</' . $tagName, $result);
    }

    /**
     * @test
     */
    public function renderBodyContentForEmptyBodyReturnsEmptyString(): void
    {
        $subject = TestingHtmlProcessor::fromHtml('<html><body></body></html>');

        $result = $subject->renderBodyContent();

        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function renderBodyContentReturnsBodyContent(): void
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
    public function renderBodyContentForBodyWithAttributeReturnsBodyContent(): void
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
    public function renderBodyContentKeepsSpecialCharactersInTextNodes(string $codeNotToBeChanged): void
    {
        $html = '<html><p>' . $codeNotToBeChanged . '</p></html>';
        $subject = TestingHtmlProcessor::fromHtml($html);

        $result = $subject->renderBodyContent();

        self::assertStringContainsString($codeNotToBeChanged, $result);
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
    ): void {
        $subject = TestingHtmlProcessor::fromHtml(
            '<html><body>' . $htmlWithNonXmlSelfClosingTags . '</body></html>'
        );

        $result = $subject->renderBodyContent();

        self::assertStringNotContainsString('</' . $tagName, $result);
    }

    /**
     * @test
     */
    public function getDomDocumentReturnsDomDocument(): void
    {
        $subject = TestingHtmlProcessor::fromHtml('<html></html>');

        self::assertInstanceOf(\DOMDocument::class, $subject->getDomDocument());
    }

    /**
     * @test
     */
    public function getDomDocumentReturnsDomDocumentProvidedToFromDomDocument(): void
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = TestingHtmlProcessor::fromDomDocument($document);

        self::assertSame($document, $subject->getDomDocument());
    }

    /**
     * @test
     */
    public function getDomDocumentWithNormalizedHtmlRepresentsTheGivenHtml(): void
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>" .
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' .
            "</head>\n<body>\n<br>\n</body>\n</html>\n";
        $subject = TestingHtmlProcessor::fromHtml($html);

        $domDocument = $subject->getDomDocument();

        self::assertEqualsHtml($html, $domDocument->saveHTML());
    }

    /**
     * @test
     *
     * @param string $htmlWithNonXmlSelfClosingTags
     * @param string $tagName
     *
     * @dataProvider nonXmlSelfClosingTagDataProvider
     */
    public function getDomDocumentVoidElementNotHasChildNodes(
        string $htmlWithNonXmlSelfClosingTags,
        string $tagName
    ): void {
        // Append a 'trap' element that might become a child node if the HTML is parsed incorrectly
        $subject = TestingHtmlProcessor::fromHtml(
            '<html><body>' . $htmlWithNonXmlSelfClosingTags . '<span>foo</span></body></html>'
        );

        $domDocument = $subject->getDomDocument();

        $voidElements = $domDocument->getElementsByTagName($tagName);
        foreach ($voidElements as $element) {
            self::assertFalse($element->hasChildNodes());
        }
    }

    /**
     * Asserts that an HTML haystack contains an HTML needle, allowing for additional newlines in the haystack that may
     * have been inserted by the `formatOutput` option of `DOMDocument`.
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    private static function assertContainsHtml(string $needle, string $haystack, string $message = ''): void
    {
        $needleMatcher = \preg_quote($needle, '%');
        $needleMatcherWithNewlines = \preg_replace(
            '%\\\\<(?:body|ul|dl|optgroup|table|tr|hr'
                . '|/(?:li|dd|dt|option|optgroup|caption|colgroup|thead|tbody|tfoot|tr|td|th'
                . '|p|dl|h[1-6]|menu|ol|pre|table|ul|address|blockquote|div|fieldset|form))\\\\>%',
            '$0\\n?+',
            $needleMatcher
        );

        self::assertMatchesRegularExpression('%' . $needleMatcherWithNewlines . '%', $haystack, $message);
    }

    /**
     * Asserts that two HTML strings are equal, allowing for whitespace differences in the HTML element itself (but not
     * its descendants) and after its closing tag.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    private static function assertEqualsHtml(string $expected, string $actual, string $message = ''): void
    {
        $normalizedExpected = self::normalizeHtmlElement($expected);
        $normalizedActual = self::normalizeHtmlElement($actual);

        self::assertSame($normalizedExpected, $normalizedActual, $message);
    }

    /**
     * Normalizes whitespace in the HTML element itself (but not its descendants) and after its closing tag, with a
     * single newline inserted or replacing whitespace at positions where whitespace may occur but is superfluous.
     *
     * @param string $html
     *
     * @return string
     */
    private static function normalizeHtmlElement(string $html): string
    {
        return \preg_replace(
            [
                '%(<html(?=[\\s>])[^>]*+>)\\s*+(<head[\\s>])%',
                '%(</head>)\\s*+(<body[\\s>])%',
                '%(</body>)\\s*+(</html>)%',
                '%(</html>)\\s*+($)%',
            ],
            "$1\n$2",
            $html
        );
    }
}
