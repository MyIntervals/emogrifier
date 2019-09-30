<?php

namespace Pelago\Tests\Unit;

use Pelago\Emogrifier;
use Pelago\Tests\Support\Traits\AssertCss;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <github@oliverklee.de>
 * @author Zoli Szabó <zoli.szabo+github@gmail.com>
 */
class EmogrifierTest extends TestCase
{
    use AssertCss;

    /**
     * @var string Common HTML markup with a variety of elements and attributes for testing with
     */
    const COMMON_TEST_HTML = '
        <html>
            <body>
                <p class="p-1"><span>some text</span></p>
                <p class="p-2"><span title="bonjour">some</span> text</p>
                <div class="div-3"><span title="buenas dias">some</span> more text</div>
                <p class="p-4" id="p4"><span title="avez-vous">some</span> more <span id="text">text</span></p>
                <p class="p-5 additional-class"><span title="buenas dias bom dia">some</span> more text</p>
                <p class="p-6"><span title="title: subtitle; author">some</span> more text</p>
                <p class="p-7"></p>
            </body>
        </html>
    ';

    /**
     * @var string
     */
    private $html5DocumentType = '<!DOCTYPE html>';

    /**
     * @var Emogrifier
     */
    private $subject = null;

    /**
     * Sets up the test case.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->subject = new Emogrifier();
        $this->subject->setDebug(true);
    }

    /**
     * @test
     */
    public function getDomDocumentReturnsDomDocument()
    {
        $subject = new Emogrifier('<html></html>');

        self::assertInstanceOf(\DOMDocument::class, $subject->getDomDocument());
    }

    /**
     * @test
     */
    public function getDomDocumentWithNormalizedHtmlRepresentsTheGivenHtml()
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>" .
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' .
            "</head>\n<body>\n<br>\n</body>\n</html>\n";
        $subject = new Emogrifier($html);

        $domDocument = $subject->getDomDocument();

        self::assertSame($html, $domDocument->saveHTML());
    }

    /**
     * @test
     */
    public function emogrifyForNoDataSetThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);

        $this->subject->emogrify();
    }

    /**
     * @return array[]
     */
    public function nonHtmlDataProvider()
    {
        return [
            'empty string' => [''],
            'null' => [null],
            'integer' => [2],
            'float' => [3.14159],
            'object' => [new \stdClass()],
        ];
    }

    /**
     * @test
     *
     * @param mixed $html
     *
     * @dataProvider nonHtmlDataProvider
     */
    public function setHtmlNoHtmlDataThrowsException($html)
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->setHtml($html);
    }

    /**
     * @test
     */
    public function emogrifyBodyContentForNoDataSetThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);

        $this->subject->emogrifyBodyContent();
    }

    /**
     * @return string[][]
     */
    public function contentWithoutHtmlTagDataProvider()
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
    public function emogrifyAddsMissingHtmlTag($html)
    {
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains('<html>', $result);
    }

    /**
     * @return string[][]
     */
    public function contentWithoutHeadTagDataProvider()
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
    public function emogrifyAddsMissingHeadTag($html)
    {
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains('<head>', $result);
    }

    /**
     * @return string[][]
     */
    public function contentWithoutBodyTagDataProvider()
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
    public function emogrifyAddsMissingBodyTag($html)
    {
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains('<body>', $result);
    }

    /**
     * @test
     */
    public function emogrifyPutsMissingBodyElementAroundBodyContent()
    {
        $this->subject->setHtml('<p>Hello</p>');

        $result = $this->subject->emogrify();

        self::assertContains('<body><p>Hello</p></body>', $result);
    }

    /**
     * @return string[][]
     */
    public function specialCharactersDataProvider()
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
    public function emogrifyKeepsSpecialCharactersInTextNodes($codeNotToBeChanged)
    {
        $html = '<html><p>' . $codeNotToBeChanged . '</p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($codeNotToBeChanged, $result);
    }

    /**
     * @test
     *
     * @param string $codeNotToBeChanged
     *
     * @dataProvider specialCharactersDataProvider
     */
    public function emogrifyBodyContentKeepsSpecialCharactersInTextNodes($codeNotToBeChanged)
    {
        $html = '<html><p>' . $codeNotToBeChanged . '</p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrifyBodyContent();

        self::assertContains($codeNotToBeChanged, $result);
    }

    /**
     * @test
     */
    public function addsMissingHtml5DocumentType()
    {
        $this->subject->setHtml('<html></html>');

        $result = $this->subject->emogrify();

        self::assertContains('<!DOCTYPE html>', $result);
    }

    /**
     * @return string[][]
     */
    public function documentTypeDataProvider()
    {
        return [
            'HTML5' => ['<!DOCTYPE html>'],
            'XHTML 1 strict' => [
                '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" ' .
                '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
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
    public function emogrifyForHtmlWithDocumentTypeKeepsDocumentType($documentType)
    {
        $html = $documentType . '<html></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($documentType, $result);
    }

    /**
     * @test
     */
    public function emogrifyAddsMissingContentTypeMetaTag()
    {
        $this->subject->setHtml('<p>Hello</p>');

        $result = $this->subject->emogrify();

        self::assertContains('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $result);
    }

    /**
     * @test
     */
    public function emogrifyNotAddsSecondContentTypeMetaTag()
    {
        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        $numberOfContentTypeMetaTags = \substr_count($result, 'Content-Type');
        self::assertSame(1, $numberOfContentTypeMetaTags);
    }

    /**
     * @return string[][]
     */
    public function wbrTagDataProvider()
    {
        return [
            'single <wbr> tag' => ['<body>foo<wbr/>bar</body>'],
            'two sibling <wbr> tags' => ['<body>foo<wbr/>bar<wbr/>baz</body>'],
            'two non-sibling <wbr> tags' => ['<body><p>foo<wbr/>bar</p><p>bar<wbr/>baz</p></body>'],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     *
     * @dataProvider wbrTagDataProvider
     */
    public function emogrifyByDefaultRemovesWbrTag($html)
    {
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertNotContains('<wbr', $result);
    }

    /**
     * @test
     *
     * @param string $html
     *
     * @dataProvider wbrTagDataProvider
     */
    public function emogrifyAfterRemoveUnprocessableHtmlTagKeepsWbrTag($html)
    {
        $numberOfWbrTags = \substr_count($html, '<wbr');
        $this->subject->setHtml($html);

        $this->subject->removeUnprocessableHtmlTag('wbr');
        $result = $this->subject->emogrify();

        self::assertSame($numberOfWbrTags, \substr_count($result, '<wbr'));
    }

    /**
     * @test
     */
    public function addUnprocessableTagRemovesEmptyTag()
    {
        $this->subject->setHtml('<body><p></p></body>');

        $this->subject->addUnprocessableHtmlTag('p');
        $result = $this->subject->emogrify();

        self::assertNotContains('<p>', $result);
    }

    /**
     * @test
     */
    public function addUnprocessableTagNotRemovesNonEmptyTag()
    {
        $this->subject->setHtml('<body><p>foobar</p></body>');

        $this->subject->addUnprocessableHtmlTag('p');
        $result = $this->subject->emogrify();

        self::assertContains('<p>', $result);
    }

    /**
     * @test
     */
    public function removeUnprocessableHtmlTagKeepsTagAgainAgain()
    {
        $this->subject->setHtml('<body><p></p></body>');

        $this->subject->addUnprocessableHtmlTag('p');
        $this->subject->removeUnprocessableHtmlTag('p');
        $result = $this->subject->emogrify();

        self::assertContains('<p>', $result);
    }

    /**
     * @return string[][]
     */
    public function matchedCssDataProvider()
    {
        // The sprintf placeholders %1$s and %2$s will automatically be replaced with CSS declarations
        // like 'color: red;' or 'text-align: left;'.
        return [
            'two declarations from one rule can apply to the same element' => [
                'html { %1$s %2$s }',
                '<html style="%1$s %2$s">',
            ],
            'two identical matchers with different rules get combined' => [
                'p { %1$s } p { %2$s }',
                '<p class="p-1" style="%1$s %2$s">',
            ],
            'two different matchers rules matching the same element get combined' => [
                'p { %1$s } .p-1 { %2$s }',
                '<p class="p-1" style="%1$s %2$s">',
            ],
            'type => one element' => ['html { %1$s }', '<html style="%1$s">'],
            'type (case-insensitive) => one element' => ['HTML { %1$s }', '<html style="%1$s">'],
            'type => first matching element' => ['p { %1$s }', '<p class="p-1" style="%1$s">'],
            'type => second matching element' => ['p { %1$s }', '<p class="p-2" style="%1$s">'],
            'class => with class' => ['.p-2 { %1$s }', '<p class="p-2" style="%1$s">'],
            'two classes s=> with both classes' => [
                '.p-5.additional-class { %1$s }',
                '<p class="p-5 additional-class" style="%1$s">',
            ],
            'type & class => type with class' => ['p.p-2 { %1$s }', '<p class="p-2" style="%1$s">'],
            'ID => with ID' => ['#p4 { %1$s }', '<p class="p-4" id="p4" style="%1$s">'],
            'type & ID => type with ID' => ['p#p4 { %1$s }', '<p class="p-4" id="p4" style="%1$s">'],
            'universal => HTML' => ['* { %1$s }', '<html style="%1$s">'],
            'universal => element with parent and children' => ['* { %1$s }', '<p class="p-1" style="%1$s">'],
            'universal => leaf element' => ['* { %1$s }', '<span style="%1$s">'],
            'attribute presence => with attribute' => ['[title] { %1$s }', '<span title="bonjour" style="%1$s">'],
            'attribute exact value, double quotes => with exact attribute match' => [
                '[title="bonjour"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'attribute exact value, single quotes => with exact match' => [
                '[title=\'bonjour\'] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            // broken: attribute exact value without quotes => with exact match
            // broken: attribute exact two-word value, double quotes => with exact attribute value match
            // broken: attribute exact two-word value, single quotes => with exact attribute value match
            // broken: attribute exact value with ~, double quotes => exact attribute match
            // broken: attribute exact value with ~, single quotes => exact attribute match
            // broken: attribute exact value with ~, no quotes => exact attribute match
            // broken: attribute value with |, double quotes => with exact match
            // broken: attribute value with |, single quotes => with exact match
            // broken: attribute value with |, no quotes => with exact match
            // broken: attribute value with ^, double quotes => with exact match
            // broken: attribute value with ^, single quotes => with exact match
            // broken: attribute value with ^, no quotes => with exact match
            // broken: attribute value with $, double quotes => with exact match
            // broken: attribute value with $, single quotes => with exact match
            // broken: attribute value with $, no quotes => with exact match
            // broken: attribute value with *, double quotes => with exact match
            // broken: attribute value with *, single quotes => with exact match
            // broken: attribute value with *, no quotes => with exact match
            // broken: type & attribute presence => with type & attribute
            'type & attribute exact value, double quotes => with type & exact attribute value match' => [
                'span[title="bonjour"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute exact value, single quotes => with type & exact attribute value match' => [
                'span[title=\'bonjour\'] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute exact value without quotes => with type & exact attribute value match' => [
                'span[title=bonjour] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute exact two-word value, double quotes => with type & exact attribute value match' => [
                'span[title="buenas dias"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'type & attribute exact four-word value, double quotes => with type & exact attribute value match' => [
                'span[title="buenas dias bom dia"] { %1$s }',
                '<span title="buenas dias bom dia" style="%1$s">',
            ],
            'type & attribute exact two-word value, single quotes => with type & exact attribute value match' => [
                'span[title=\'buenas dias\'] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'type & attribute exact four-word value, single quotes => with type & exact attribute value match' => [
                'span[title=\'buenas dias bom dia\'] { %1$s }',
                '<span title="buenas dias bom dia" style="%1$s">',
            ],
            'type & attribute value with ~, double quotes => with type & exact attribute match' => [
                'span[title~="bonjour"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with ~, single quotes => with type & exact attribute match' => [
                'span[title~=\'bonjour\'] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with ~, no quotes => with type & exact attribute match' => [
                'span[title~=bonjour] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with ~, double quotes => with type & word as 1st of 2 in attribute' => [
                'span[title~="buenas"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'type & attribute value with ~, double quotes => with type & word as 2nd of 2 in attribute' => [
                'span[title~="dias"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'type & attribute value with ~, double quotes => with type & word as 1st of 4 in attribute' => [
                'span[title~="buenas"] { %1$s }',
                '<span title="buenas dias bom dia" style="%1$s">',
            ],
            'type & attribute value with ~, double quotes => with type & word as 2nd of 4 in attribute' => [
                'span[title~="dias"] { %1$s }',
                '<span title="buenas dias bom dia" style="%1$s">',
            ],
            'type & attribute value with ~, double quotes => with type & word as last of 4 in attribute' => [
                'span[title~="dia"] { %1$s }',
                '<span title="buenas dias bom dia" style="%1$s">',
            ],
            'type & attribute value with |, double quotes => with exact match' => [
                'span[title|="bonjour"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with |, single quotes => with exact match' => [
                'span[title|=\'bonjour\'] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with |, no quotes => with exact match' => [
                'span[title|=bonjour] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & two-word attribute value with |, double quotes => with exact match' => [
                'span[title|="buenas dias"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'type & attribute value with |, double quotes => with match before hyphen & another word' => [
                'span[title|="avez"] { %1$s }',
                '<span title="avez-vous" style="%1$s">',
            ],
            'type & attribute value with ^, double quotes => with exact match' => [
                'span[title^="bonjour"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with ^, single quotes => with exact match' => [
                'span[title^=\'bonjour\'] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with ^, no quotes => with exact match' => [
                'span[title^=bonjour] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            // broken: type & two-word attribute value with ^, double quotes => with exact match
            'type & attribute value with ^, double quotes => with prefix math' => [
                'span[title^="bon"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with ^, double quotes => with match before another word' => [
                'span[title^="buenas"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'type & attribute value with $, double quotes => with exact match' => [
                'span[title$="bonjour"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with $, single quotes => with exact match' => [
                'span[title$=\'bonjour\'] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with $, no quotes => with exact match' => [
                'span[title$=bonjour] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & two-word attribute value with $, double quotes => with exact match' => [
                'span[title$="buenas dias"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'type & attribute value with $, double quotes => with suffix math' => [
                'span[title$="jour"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with $, double quotes => with match after another word' => [
                'span[title$="dias"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'type & two-word attribute value with *, double quotes => with exact match' => [
                'span[title*="buenas dias"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'type & attribute value with *, double quotes => with prefix math' => [
                'span[title*="bon"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with *, double quotes => with suffix math' => [
                'span[title*="jour"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with *, double quotes => with substring math' => [
                'span[title*="njo"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'type & attribute value with *, double quotes => with match before another word' => [
                'span[title*="buenas"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'type & attribute value with *, double quotes => with match after another word' => [
                'span[title*="dias"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'type & special characters attribute value with *, double quotes => with substring match' => [
                'span[title*=": subtitle; author"] { %1$s }',
                '<span title="title: subtitle; author" style="%1$s">',
            ],
            // broken: type & attribute exact value, case insensitive => with case insensitive attribute value match
            // broken: type & attribute value with ~, case insensitive => with case insensitive attribute word match
            // broken: type & attribute value with |, case insensitive => with case insensitive match before hyphen
            // broken: type & attribute value with ^, case insensitive => with case insensitive prefix math
            // broken: type & attribute value with $, case insensitive => with case insensitive suffix math
            // broken: type & attribute value with *, case insensitive => with case insensitive substring math
            'adjacent => 2nd of many' => ['p + p { %1$s }', '<p class="p-2" style="%1$s">'],
            'adjacent => last of many' => ['p + p { %1$s }', '<p class="p-7" style="%1$s">'],
            'adjacent (without space after +) => last of many' => ['p +p { %1$s }', '<p class="p-7" style="%1$s">'],
            'adjacent (without space before +) => last of many' => ['p+ p { %1$s }', '<p class="p-7" style="%1$s">'],
            'adjacent (without space before or after +) => last of many' => [
                'p+p { %1$s }',
                '<p class="p-7" style="%1$s">',
            ],
            'child (with spaces around >) => direct child' => ['p > span { %1$s }', '<span style="%1$s">'],
            'child (without space after >) => direct child' => ['p >span { %1$s }', '<span style="%1$s">'],
            'child (without space before >) => direct child' => ['p> span { %1$s }', '<span style="%1$s">'],
            'child (without space before or after >) => direct child' => ['p>span { %1$s }', '<span style="%1$s">'],
            'descendant => child' => ['p span { %1$s }', '<span style="%1$s">'],
            'descendant => grandchild' => ['body span { %1$s }', '<span style="%1$s">'],
            'adjacent universal => 2nd of many' => ['p + * { %1$s }', '<p class="p-2" style="%1$s">'],
            'adjacent universal => last of many' => ['p + * { %1$s }', '<p class="p-7" style="%1$s">'],
            'adjacent of universal => 2nd of many' => ['* + p { %1$s }', '<p class="p-2" style="%1$s">'],
            'adjacent of universal => last of many' => ['* + p { %1$s }', '<p class="p-7" style="%1$s">'],
            // broken: child universal => direct child
            // broken: child of universal => direct child
            'descendent universal => child' => ['p * { %1$s }', '<span style="%1$s">'],
            'descendent universal => grandchild' => ['body * { %1$s }', '<span style="%1$s">'],
            // broken: descandant of universal => child
            // broken: descandant of universal => grandchild
            // broken: descendent attribute presence => with attribute
            // broken: descendent attribute exact value => with exact attribute match
            // broken: descendent type & attribute presence => with type & attribute
            'descendent type & attribute exact value => with type & exact attribute match' => [
                'body span[title="bonjour"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'descendent type & attribute exact two-word value => with type & exact attribute match' => [
                'body span[title="buenas dias"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'descendent type & attribute value with ~ => with type & exact attribute match' => [
                'body span[title~="bonjour"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'descendent type & attribute value with ~ => with type & word as 1st of 2 in attribute' => [
                'body span[title~="buenas"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
            'descendent type & attribute value with ^ => with type & attribute prefix match' => [
                'p span[title^=bon] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'descendant of type & class: type & attribute exact value, no quotes => with type & exact match (#381)' => [
                'p.p-2 span[title=bonjour] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'descendant of attribute presence => parent with attribute' => [
                '[class] span { %1$s }',
                '<p class="p-1"><span style="%1$s">',
            ],
            'descendant of attribute exact value => parent with type & exact attribute match' => [
                '[id="p4"] span { %1$s }',
                '<p class="p-4" id="p4"><span title="avez-vous" style="%1$s">',
            ],
            // broken: descendant of type & attribute presence => parent with type & attribute
            'descendant of type & attribute exact value => parent with type & exact attribute match' => [
                'p[id="p4"] span { %1$s }',
                '<p class="p-4" id="p4"><span title="avez-vous" style="%1$s">',
            ],
            // broken: descendant of type & attribute exact two-word value => parent with type & exact attribute match
            //         (exact match doesn't currently match hyphens, which would be needed to match the class attribute)
            'descendant of type & attribute value with ~ => parent with type & exact attribute match' => [
                'p[class~="p-1"] span { %1$s }',
                '<p class="p-1"><span style="%1$s">',
            ],
            'descendant of type & attribute value with ~ => parent with type & word as 1st of 2 in attribute' => [
                'p[class~="p-5"] span { %1$s }',
                '<p class="p-5 additional-class"><span title="buenas dias bom dia" style="%1$s">',
            ],
            // broken: first-child => 1st of many
            'type & :first-child => 1st of many' => ['p:first-child { %1$s }', '<p class="p-1" style="%1$s">'],
            // broken: last-child => last of many
            'type & :last-child => last of many' => ['p:last-child { %1$s }', '<p class="p-7" style="%1$s">'],
            // broken: only-child
            // broken: nth-child without preceding component - OK if preceded by universal selector
            ':nth-child(even) => 2nd of many' => ['*:nth-child(even) { %1$s }', '<p class="p-2" style="%1$s">'],
            ':nth-child(even) => 4th of many' => ['*:nth-child(even) { %1$s }', '<p class="p-4" id="p4" style="%1$s">'],
            ':nth-child(2n) => 2nd of many' => ['*:nth-child(2n) { %1$s }', '<p class="p-2" style="%1$s">'],
            ':nth-child(2n) => 4th of many' => ['*:nth-child(2n) { %1$s }', '<p class="p-4" id="p4" style="%1$s">'],
            ':nth-child(3) => 3rd of many' => ['*:nth-child(3) { %1$s }', '<div class="div-3" style="%1$s">'],
            // broken: nth-child(Xn+Y)
            'type & :nth-child(even) => 2nd of many' => ['p:nth-child(even) { %1$s }', '<p class="p-2" style="%1$s">'],
            // broken: nth-last-child
            // broken: nth-of-type without preceding type
            'type & :nth-of-type(even) => 2nd of many of type' => [
                'p:nth-of-type(even) { %1$s }',
                '<p class="p-2" style="%1$s">',
            ],
            'type & :nth-of-type(even) => 4th of many of type' => [
                'p:nth-of-type(even) { %1$s }',
                '<p class="p-5 additional-class" style="%1$s">',
            ],
            'type & :nth-of-type(2n) => 2nd of many of type' => [
                'p:nth-of-type(2n) { %1$s }',
                '<p class="p-2" style="%1$s">',
            ],
            'type & :nth-of-type(2n) => 4th of many of type' => [
                'p:nth-of-type(2n) { %1$s }',
                '<p class="p-5 additional-class" style="%1$s">',
            ],
            'type & :nth-of-type(3) => 3rd of many of type' => [
                'p:nth-of-type(3) { %1$s }',
                '<p class="p-4" id="p4" style="%1$s">',
            ],
            // broken: nth-of-type(Xn+Y)
            // broken: nth-last-of-type
            // broken: :not with type => other type
            // broken: :not with class => no class
            // broken: :not with class => other class
            'type & :not with class => without class' => ['span:not(.foo) { %1$s }', '<span style="%1$s">'],
            'type & :not with class => with other class' => ['p:not(.foo) { %1$s }', '<p class="p-1" style="%1$s">'],
        ];
    }

    /**
     * @test
     *
     * @param string $css CSS statements, potentially with %1$s and $2$s placeholders for a CSS declaration
     * @param string $expectedHtml HTML, potentially with %1$s and $2$s placeholders for a CSS declaration
     *
     * @dataProvider matchedCssDataProvider
     */
    public function emogrifyAppliesCssToMatchingElements($css, $expectedHtml)
    {
        $cssDeclaration1 = 'color: red;';
        $cssDeclaration2 = 'text-align: left;';
        $this->subject->setHtml(self::COMMON_TEST_HTML);
        $this->subject->setCss(\sprintf($css, $cssDeclaration1, $cssDeclaration2));

        $result = $this->subject->emogrify();

        self::assertContains(\sprintf($expectedHtml, $cssDeclaration1, $cssDeclaration2), $result);
    }

    /**
     * @return string[][]
     */
    public function nonMatchedCssDataProvider()
    {
        // The sprintf placeholders %1$s and %2$s will automatically be replaced with CSS declarations
        // like 'color: red;' or 'text-align: left;'.
        return [
            'type => not other type' => ['html { %1$s }', '<body>'],
            'class => not other class' => ['.p-2 { %1$s }', '<p class="p-1">'],
            'class => not without class' => ['.p-2 { %1$s }', '<body>'],
            'two classes => not only first class' => ['.p-1.another-class { %1$s }', '<p class="p-1">'],
            'two classes => not only second class' => ['.another-class.p-1 { %1$s }', '<p class="p-1">'],
            'type & class => not only type' => ['html.p-1 { %1$s }', '<html>'],
            'type & class => not only class' => ['html.p-1 { %1$s }', '<p class="p-1">'],
            'ID => not other ID' => ['#yeah { %1$s }', '<p class="p-4" id="p4">'],
            'ID => not without ID' => ['#yeah { %1$s }', '<span>'],
            'type & ID => not other type with that ID' => ['html#p4 { %1$s }', '<p class="p-4" id="p4">'],
            'type & ID => not that type with other ID' => ['p#p5 { %1$s }', '<p class="p-4" id="p4">'],
            'attribute presence => not element without that attribute' => ['[title] { %1$s }', '<span>'],
            'attribute exact value => not element without that attribute' => ['[title="bonjour"] { %1$s }', '<span>'],
            'attribute exact value => not element with different attribute value' => [
                '[title="hi"] { %1$s }',
                '<span title="bonjour">',
            ],
            'attribute exact value => not element with only substring match in attribute value' => [
                '[title="njo"] { %1$s }',
                '<span title="bonjour">',
            ],
            'type & attribute value with ~ => not element with only prefix match in attribute value' => [
                'span[title~="bon"] { %1$s }',
                '<span title="bonjour">',
            ],
            'type & attribute value with |, double quotes => not element with match after another word & hyphen' => [
                'span[title|="vous"] { %1$s }',
                '<span title="avez-vous">',
            ],
            'type & attribute value with ^ => not element with only substring match in attribute value' => [
                'span[title^="njo"] { %1$s }',
                '<span title="bonjour">',
            ],
            'type & attribute value with ^, double quotes => not element with only suffix match in attribute value' => [
                'span[title^="jour"] { %1$s }',
                '<span title="bonjour">',
            ],
            'type & attribute value with $ => not element with only substring match in attribute value' => [
                'span[title$="njo"] { %1$s }',
                '<span title="bonjour">',
            ],
            'type & attribute value with $, double quotes => not element with only prefix match in attribute value' => [
                'span[title$="bon"] { %1$s }',
                '<span title="bonjour">',
            ],
            'type & attribute value with * => not element with different attribute value' => [
                'span[title*="hi"] { %1$s }',
                '<span title="bonjour">',
            ],
            'adjacent => not 1st of many' => ['p + p { %1$s }', '<p class="p-1">'],
            'child => not grandchild' => ['html > span { %1$s }', '<span>'],
            'child => not parent' => ['span > html { %1$s }', '<html>'],
            'descendant => not sibling' => ['span span { %1$s }', '<span>'],
            'descendant => not parent' => ['p body { %1$s }', '<body>'],
            'adjacent universal => not 1st of many' => ['p + * { %1$s }', '<p class="p-1">'],
            'adjacent universal => not last of many' => ['.p-2 + * { %1$s }', '<p class="p-7">'],
            'adjacent universal => not previous of many' => ['.p-2 + * { %1$s }', '<p class="p-1">'],
            'adjacent of universal => not 1st of many' => ['* + p { %1$s }', '<p class="p-1">'],
            'descendent universal => not parent' => ['p *', '<body>'],
            'descendent universal => not self' => ['p *', '<p class="p-1">'],
            'descendent type & attribute value with ^ => not element with only substring match in attribute value' => [
                'p span[title^=njo] { %1$s }',
                '<span title="bonjour">',
            ],
            'type & :first-child => not 2nd of many' => ['p:first-child { %1$s }', '<p class="p-2">'],
            'type & :first-child => not last of many' => ['p:first-child { %1$s }', '<p class="p-7">'],
            'type & :last-child => not 1st of many' => ['p:last-child { %1$s }', '<p class="p-1">'],
            'type & :last-child => not 2nd of many' => ['p:last-child { %1$s }', '<p class="p-2">'],
            ':nth-child(even) => not 1st of many' => ['*:nth-child(even) { %1$s }', '<p class="p-1">'],
            ':nth-child(even) => not 3rd of many' => ['*:nth-child(even) { %1$s }', '<div class="div-3">'],
            ':nth-child(2n) => not 1st of many' => ['*:nth-child(2n) { %1$s }', '<p class="p-1">'],
            ':nth-child(2n) => not 3rd of many' => ['*:nth-child(2n) { %1$s }', '<div class="div-3">'],
            ':nth-child(3) => not 1st of many' => ['*:nth-child(3) { %1$s }', '<p class="p-1">'],
            ':nth-child(3) => not 2nd of many' => ['*:nth-child(3) { %1$s }', '<p class="p-2">'],
            ':nth-child(3) => not 4th of many' => ['*:nth-child(3) { %1$s }', '<p class="p-4" id="p4">'],
            ':nth-child(3) => not 6th of many' => ['*:nth-child(3) { %1$s }', '<p class="p-6">'],
            'type & :nth-of-type(even) => not 1st of many of type' => [
                'p:nth-of-type(even) { %1$s }',
                '<p class="p-1">',
            ],
            'type & :nth-of-type(even) => not 3rd of many of type' => [
                'p:nth-of-type(even) { %1$s }',
                '<p class="p-4" id="p4">',
            ],
            'type & :nth-of-type(2n) => not 1st of many of type' => ['p:nth-of-type(2n) { %1$s }', '<p class="p-1">'],
            'type & :nth-of-type(2n) => not 3rd of many of type' => [
                'p:nth-of-type(2n) { %1$s }',
                '<p class="p-4" id="p4">',
            ],
            'type & :nth-of-type(3) => not 1st of many of type' => ['p:nth-of-type(3) { %1$s }', '<p class="p-1">'],
            'type & :nth-of-type(3) => not 2nd of many of type' => ['p:nth-of-type(3) { %1$s }', '<p class="p-2">'],
            'type & :nth-of-type(3) => not 4th of many of type' => [
                'p:nth-of-type(3) { %1$s }',
                '<p class="p-5 additional-class">',
            ],
            'type & :nth-of-type(3) => not 6th of many of type' => ['p:nth-of-type(3) { %1$s }', '<p class="p-7">'],
            'type & :not with class => not with class' => ['p:not(.p-1) { %1$s }', '<p class="p-1">'],
        ];
    }

    /**
     * @test
     *
     * @param string $css CSS statements, potentially with %1$s and $2$s placeholders for a CSS declaration
     * @param string $expectedHtml HTML, potentially with %1$s and $2$s placeholders for a CSS declaration
     *
     * @dataProvider nonMatchedCssDataProvider
     */
    public function emogrifyNotAppliesCssToNonMatchingElements($css, $expectedHtml)
    {
        $cssDeclaration1 = 'color: red;';
        $cssDeclaration2 = 'text-align: left;';
        $this->subject->setHtml(self::COMMON_TEST_HTML);
        $this->subject->setCss(\sprintf($css, $cssDeclaration1, $cssDeclaration2));

        $result = $this->subject->emogrify();

        self::assertContains(\sprintf($expectedHtml, $cssDeclaration1, $cssDeclaration2), $result);
    }

    /**
     * Provides data to test the following selector specificity ordering:
     *     * < t < 2t < . < .+t < .+2t < 2. < 2.+t < 2.+2t
     *     < # < #+t < #+2t < #+. < #+.+t < #+.+2t < #+2. < #+2.+t < #+2.+2t
     *     < 2# < 2#+t < 2#+2t < 2#+. < 2#+.+t < 2#+.+2t < 2#+2. < 2#+2.+t < 2#+2.+2t
     * where '*' is the universal selector, 't' is a type selector, '.' is a class selector, and '#' is an ID selector.
     *
     * Also confirm up to 99 class selectors are supported (much beyond this would require a more complex comparator).
     *
     * Specificity ordering for selectors involving pseudo-classes, attributes and `:not` is covered through the
     * combination of these tests and the equal specificity tests and thus does not require explicit separate testing.
     *
     * @return string[][]
     */
    public function differentCssSelectorSpecificityDataProvider()
    {
        /**
         * @var string[] Selectors targeting `<span id="text">` with increasing specificity
         */
        $selectors = [
            'universal' => '*',
            'type' => 'span',
            '2 types' => 'p span',
            'class' => '.p-4 *',
            'class & type' => '.p-4 span',
            'class & 2 types' => 'p.p-4 span',
            '2 classes' => '.p-4.p-4 *',
            '2 classes & type' => '.p-4.p-4 span',
            '2 classes & 2 types' => 'p.p-4.p-4 span',
            'ID' => '#text',
            'ID & type' => 'span#text',
            'ID & 2 types' => 'p span#text',
            'ID & class' => '.p-4 #text',
            'ID & class & type' => '.p-4 span#text',
            'ID & class & 2 types' => 'p.p-4 span#text',
            'ID & 2 classes' => '.p-4.p-4 #text',
            'ID & 2 classes & type' => '.p-4.p-4 span#text',
            'ID & 2 classes & 2 types' => 'p.p-4.p-4 span#text',
            '2 IDs' => '#p4 #text',
            '2 IDs & type' => '#p4 span#text',
            '2 IDs & 2 types' => 'p#p4 span#text',
            '2 IDs & class' => '.p-4#p4 #text',
            '2 IDs & class & type' => '.p-4#p4 span#text',
            '2 IDs & class & 2 types' => 'p.p-4#p4 span#text',
            '2 IDs & 2 classes' => '.p-4.p-4#p4 #text',
            '2 IDs & 2 classes & type' => '.p-4.p-4#p4 span#text',
            '2 IDs & 2 classes & 2 types' => 'p.p-4.p-4#p4 span#text',
        ];

        $datasets = [];
        $previousSelector = '';
        $previousDescription = '';
        foreach ($selectors as $description => $selector) {
            if ($previousSelector !== '') {
                $datasets[$description . ' more specific than ' . $previousDescription] = [
                    '<span id="text"',
                    $previousSelector,
                    $selector,
                ];
            }
            $previousSelector = $selector;
            $previousDescription = $description;
        }

        // broken: class more specific than 99 types (requires support for chaining `:not(h1):not(h1)...`)
        $datasets['ID more specific than 99 classes'] = [
            '<p class="p-4" id="p4"',
            \str_repeat('.p-4', 99),
            '#p4',
        ];

        return $datasets;
    }

    /**
     * @test
     *
     * @param string $matchedTagPart Tag expected to be matched by both selectors, without the closing '>',
     *                               e.g. '<p class="p-1"'
     * @param string $lessSpecificSelector A selector expression
     * @param string $moreSpecificSelector Some other, more specific selector expression
     *
     * @dataProvider differentCssSelectorSpecificityDataProvider
     */
    public function emogrifyAppliesMoreSpecificCssSelectorToMatchingElements(
        $matchedTagPart,
        $lessSpecificSelector,
        $moreSpecificSelector
    ) {
        $this->subject->setHtml(self::COMMON_TEST_HTML);
        $this->subject->setCss(
            $lessSpecificSelector . ' { color: red; } ' .
            $moreSpecificSelector . ' { color: green; } ' .
            $moreSpecificSelector . ' { background-color: green; } ' .
            $lessSpecificSelector . ' { background-color: red; }'
        );

        $result = $this->subject->emogrify();

        self::assertContains($matchedTagPart . ' style="color: green; background-color: green;"', $result);
    }

    /**
     * @return string[][]
     */
    public function equalCssSelectorSpecificityDataProvider()
    {
        return [
            // pseudo-class
            'pseudo-class as specific as class' => ['<p class="p-1"', '*:first-child', '.p-1'],
            'type & pseudo-class as specific as type & class' => ['<p class="p-1"', 'p:first-child', 'p.p-1'],
            'class & pseudo-class as specific as two classes' => ['<p class="p-1"', '.p-1:first-child', '.p-1.p-1'],
            'ID & pseudo-class as specific as ID & class' => [
                '<span title="avez-vous"',
                '#p4 *:first-child',
                '#p4.p-4 *',
            ],
            '2 types & 2 classes & 2 IDs & pseudo-class as specific as 2 types & 3 classes & 2 IDs' => [
                '<span id="text"',
                'p.p-4.p-4#p4 span#text:last-child',
                'p.p-4.p-4.p-4#p4 span#text',
            ],
            // attribute
            'attribute as specific as class' => ['<span title="bonjour"', '[title="bonjour"]', '.p-2 *'],
            'type & attribute as specific as type & class' => [
                '<span title="bonjour"',
                'span[title="bonjour"]',
                '.p-2 span',
            ],
            'class & attribute as specific as two classes' => ['<p class="p-4" id="p4"', '.p-4[id="p4"]', '.p-4.p-4'],
            'ID & attribute as specific as ID & class' => ['<p class="p-4" id="p4"', '#p4[id="p4"]', '#p4.p-4'],
            '2 types & 2 classes & 2 IDs & attribute as specific as 2 types & 3 classes & 2 IDs' => [
                '<span id="text"',
                'p.p-4.p-4#p4[id="p4"] span#text',
                'p.p-4.p-4.p-4#p4 span#text',
            ],
            // :not
            // ideally these tests would be more minimal with just combinators and universal selectors in the :not
            // argument, however Symfony CssSelector only supports simple (single-element) selectors here
            ':not with type as specific as type and universal' => ['<p class="p-1"', '*:not(html)', 'html *'],
            'type & :not with type as specific as 2 types' => ['<p class="p-1"', 'p:not(html)', 'html p'],
            'class & :not with type as specific as type & class' => ['<p class="p-1"', '.p-1:not(html)', 'html .p-1'],
            'ID & :not with type as specific as type & ID' => ['<p class="p-4" id="p4"', '#p4:not(html)', 'html #p4'],
            '2 types & 2 classes & 2 IDs & :not with type as specific as 3 types & 2 classes & 2 IDs' => [
                '<span id="text"',
                'p.p-4.p-4#p4 span#text:not(html)',
                'html p.p-4.p-4#p4 span#text',
            ],
            // argument of :not
            ':not with type as specific as type' => ['<p class="p-1"', '*:not(h1)', 'p'],
            ':not with class as specific as class' => ['<p class="p-1"', '*:not(.p-2)', '.p-1'],
            ':not with ID as specific as ID' => ['<p class="p-4" id="p4"', '*:not(#p1)', '#p4'],
            // broken: :not with 2 types & 2 classes & 2 IDs as specific as 2 types & 2 classes & 2 IDs
            //         (`*:not(.p-1 #p1)`, i.e. with both class and ID, causes "Invalid type in selector")
        ];
    }

    /**
     * @test
     *
     * @param string $matchedTagPart Tag expected to be matched by both selectors, without the closing '>',
     *                               e.g. '<p class="p-1"'
     * @param string $selector1 A selector expression
     * @param string $selector2 Some other, equally specific selector expression
     *
     * @dataProvider equalCssSelectorSpecificityDataProvider
     */
    public function emogrifyAppliesLaterEquallySpecificCssSelectorToMatchingElements(
        $matchedTagPart,
        $selector1,
        $selector2
    ) {
        $this->subject->setHtml(self::COMMON_TEST_HTML);
        $this->subject->setCss(
            $selector1 . ' { color: red; } ' .
            $selector2 . ' { color: green; } ' .
            $selector2 . ' { background-color: red; } ' .
            $selector1 . ' { background-color: green; }'
        );

        $result = $this->subject->emogrify();

        self::assertContains($matchedTagPart . ' style="color: green; background-color: green;"', $result);
    }

    /**
     * @return string[][]
     */
    public function cssDeclarationWhitespaceDroppingDataProvider()
    {
        return [
            'no whitespace, trailing semicolon' => ['color:#000;'],
            'no whitespace, no trailing semicolon' => ['color:#000'],
            'space after colon, no trailing semicolon' => ['color: #000'],
            'space before colon, no trailing semicolon' => ['color :#000'],
            'space before property name, no trailing semicolon' => [' color:#000'],
            'space before trailing semicolon' => [' color:#000 ;'],
            'space after trailing semicolon' => [' color:#000; '],
            'space after property value, no trailing semicolon' => [' color:#000 '],
            'space after property value, trailing semicolon' => [' color:#000; '],
            'newline before property name, trailing semicolon' => ["\ncolor:#000;"],
            'newline after property semicolon' => ["color:#000;\n"],
            'newline before colon, trailing semicolon' => ["color\n:#000;"],
            'newline after colon, trailing semicolon' => ["color:\n#000;"],
            'newline after semicolon' => ["color:#000\n;"],
        ];
    }

    /**
     * @test
     *
     * @param string $cssDeclaration the CSS declaration block (without the curly braces)
     *
     * @dataProvider cssDeclarationWhitespaceDroppingDataProvider
     */
    public function emogrifyTrimsWhitespaceFromCssDeclarations($cssDeclaration)
    {
        $this->subject->setHtml('<html></html>');
        $this->subject->setCss('html {' . $cssDeclaration . '}');

        $result = $this->subject->emogrify();

        self::assertContains('<html style="color: #000;">', $result);
    }

    /**
     * @return string[][]
     */
    public function formattedCssDeclarationDataProvider()
    {
        return [
            'one declaration' => ['color: #000;', 'color: #000;'],
            'one declaration with dash in property name' => ['font-weight: bold;', 'font-weight: bold;'],
            'one declaration with space in property value' => ['margin: 0 4px;', 'margin: 0 4px;'],
            'two declarations separated by semicolon' => ['color: #000;width: 3px;', 'color: #000; width: 3px;'],
            'two declarations separated by semicolon & space'
            => ['color: #000; width: 3px;', 'color: #000; width: 3px;'],
            'two declarations separated by semicolon & linefeed' => [
                "color: #000;\nwidth: 3px;",
                'color: #000; width: 3px;',
            ],
            'two declarations separated by semicolon & Windows line ending' => [
                "color: #000;\r\nwidth: 3px;",
                'color: #000; width: 3px;',
            ],
            'one declaration with leading dash in property name' => [
                '-webkit-text-size-adjust:none;',
                '-webkit-text-size-adjust: none;',
            ],
            'one declaration with linefeed in property value' => [
                "text-shadow:\n1px 1px 3px #000,\n1px 1px 1px #000;",
                "text-shadow: 1px 1px 3px #000,\n1px 1px 1px #000;",
            ],
            'one declaration with Windows line ending in property value' => [
                "text-shadow:\r\n1px 1px 3px #000,\r\n1px 1px 1px #000;",
                "text-shadow: 1px 1px 3px #000,\r\n1px 1px 1px #000;",
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $cssDeclarationBlock the CSS declaration block (without the curly braces)
     * @param string $expectedStyleAttributeContent the expected value of the style attribute
     *
     * @dataProvider formattedCssDeclarationDataProvider
     */
    public function emogrifyFormatsCssDeclarations($cssDeclarationBlock, $expectedStyleAttributeContent)
    {
        $this->subject->setHtml('<html></html>');
        $this->subject->setCss('html {' . $cssDeclarationBlock . '}');

        $result = $this->subject->emogrify();

        self::assertContains('<html style="' . $expectedStyleAttributeContent . '">', $result);
    }

    /**
     * @return string[][]
     */
    public function invalidDeclarationDataProvider()
    {
        return [
            'missing dash in property name' => ['font weight: bold;'],
            'invalid character in property name' => ['-9webkit-text-size-adjust:none;'],
            'missing :' => ['-webkit-text-size-adjust none'],
            'missing value' => ['-webkit-text-size-adjust :'],
        ];
    }

    /**
     * @test
     *
     * @param string $cssDeclarationBlock the CSS declaration block (without the curly braces)
     *
     * @dataProvider invalidDeclarationDataProvider
     */
    public function emogrifyDropsInvalidCssDeclaration($cssDeclarationBlock)
    {
        $this->subject->setHtml('<html></html>');
        $this->subject->setCss('html {' . $cssDeclarationBlock . '}');

        $result = $this->subject->emogrify();

        self::assertContains('<html>', $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsExistingStyleAttributes()
    {
        $styleAttribute = 'style="color: #ccc;"';
        $this->subject->setHtml('<html ' . $styleAttribute . '></html>');

        $result = $this->subject->emogrify();

        self::assertContains($styleAttribute, $result);
    }

    /**
     * @test
     */
    public function emogrifyAddsNewCssBeforeExistingStyle()
    {
        $styleAttributeValue = 'color: #ccc;';
        $this->subject->setHtml('<html style="' . $styleAttributeValue . '"></html>');
        $cssDeclarations = 'margin: 0 2px;';
        $css = 'html {' . $cssDeclarations . '}';
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains('style="' . $cssDeclarations . ' ' . $styleAttributeValue . '"', $result);
    }

    /**
     * @test
     */
    public function emogrifyCanMatchMinifiedCss()
    {
        $this->subject->setHtml('<html><p></p></html>');
        $this->subject->setCss('p{color:blue;}html{color:red;}');

        $result = $this->subject->emogrify();

        self::assertContains('<html style="color: red;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyLowercasesAttributeNamesFromStyleAttributes()
    {
        $this->subject->setHtml('<html style="COLOR:#ccc;"></html>');

        $result = $this->subject->emogrify();

        self::assertContains('style="color: #ccc;"', $result);
    }

    /**
     * @test
     */
    public function emogrifyLowercasesAttributeNamesFromPassedInCss()
    {
        $this->subject->setHtml('<html></html>');
        $this->subject->setCss('html {mArGiN:0 2pX;}');

        $result = $this->subject->emogrify();

        self::assertContains('style="margin: 0 2pX;"', $result);
    }

    /**
     * @test
     */
    public function emogrifyPreservesCaseForAttributeValuesFromPassedInCss()
    {
        $cssDeclaration = "content: 'Hello World';";
        $this->subject->setHtml('<html><body><p>target</p></body></html>');
        $this->subject->setCss('p {' . $cssDeclaration . '}');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="' . $cssDeclaration . '">target</p>', $result);
    }

    /**
     * @test
     */
    public function emogrifyPreservesCaseForAttributeValuesFromParsedStyleBlock()
    {
        $cssDeclaration = "content: 'Hello World';";
        $this->subject->setHtml(
            '<html><head><style>p {' . $cssDeclaration . '}</style></head><body><p>target</p></body></html>'
        );

        $result = $this->subject->emogrify();

        self::assertContains('<p style="' . $cssDeclaration . '">target</p>', $result);
    }

    /**
     * @test
     */
    public function emogrifyRemovesStyleNodes()
    {
        $this->subject->setHtml('<html><style type="text/css"></style></html>');

        $result = $this->subject->emogrify();

        self::assertNotContains('<style', $result);
    }

    /**
     * @test
     */
    public function emogrifyInDebugModeForInvalidCssSelectorThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->setDebug(true);

        $this->subject->setHtml(
            '<html><style type="text/css">p{color:red;} <style data-x="1">html{cursor:text;}</style></html>'
        );

        $this->subject->emogrify();
    }

    /**
     * @test
     */
    public function emogrifyNotInDebugModeIgnoresInvalidCssSelectors()
    {
        $this->subject->setDebug(false);

        $html = '<html><style type="text/css">' .
            'p{color:red;} <style data-x="1">html{cursor:text;} p{background-color:blue;}</style> ' .
            '<body><p></p></body></html>';
        $this->subject->setHtml($html);

        $html = $this->subject->emogrify();

        self::assertContains('color: red', $html);
        self::assertContains('background-color: blue', $html);
    }

    /**
     * @test
     */
    public function emogrifyByDefaultIgnoresInvalidCssSelectors()
    {
        $subject = new Emogrifier();

        $html = '<html><style type="text/css">' .
            'p{color:red;} <style data-x="1">html{cursor:text;} p{background-color:blue;}</style> ' .
            '<body><p></p></body></html>';
        $subject->setHtml($html);

        $html = $subject->emogrify();
        self::assertContains('color: red', $html);
        self::assertContains('background-color: blue', $html);
    }

    /**
     * Data provider for things that should be left out when applying the CSS.
     *
     * @return string[][]
     */
    public function unneededCssThingsDataProvider()
    {
        return [
            'CSS comments with one asterisk' => ['p {color: #000;/* black */}', 'black'],
            'CSS comments with two asterisks' => ['p {color: #000;/** black */}', 'black'],
            '@charset directive' => ['@charset "UTF-8";', '@charset'],
            'style in "aural" media type rule' => ['@media aural {p {color: #000;}}', '#000'],
            'style in "braille" media type rule' => ['@media braille {p {color: #000;}}', '#000'],
            'style in "embossed" media type rule' => ['@media embossed {p {color: #000;}}', '#000'],
            'style in "handheld" media type rule' => ['@media handheld {p {color: #000;}}', '#000'],
            'style in "projection" media type rule' => ['@media projection {p {color: #000;}}', '#000'],
            'style in "speech" media type rule' => ['@media speech {p {color: #000;}}', '#000'],
            'style in "tty" media type rule' => ['@media tty {p {color: #000;}}', '#000'],
            'style in "tv" media type rule' => ['@media tv {p {color: #000;}}', '#000'],
            'style in "tv" media type rule with extra spaces' => [
                '  @media  tv  {  p  {  color  :  #000  ;  }  }  ',
                '#000',
            ],
            'style in "tv" media type rule with linefeeds' => [
                "\n@media\ntv\n{\np\n{\ncolor\n:\n#000\n;\n}\n}\n",
                '#000',
            ],
            'style in "tv" media type rule with Windows line endings' => [
                "\r\n@media\r\ntv\r\n{\r\np\r\n{\r\ncolor\r\n:\r\n#000\r\n;\r\n}\r\n}\r\n",
                '#000',
            ],
            'style in "only tv" media type rule' => ['@media only tv {p {color: #000;}}', '#000'],
            'style in "only tv" media type rule with extra spaces' => [
                '  @media  only  tv  {  p  {  color  :  #000  ;  }  }  ',
                '#000',
            ],
            'style in "only tv" media type rule with linefeeds' => [
                "\n@media\nonly\ntv\n{\np\n{\ncolor\n:\n#000\n;\n}\n}\n",
                '#000',
            ],
            'style in "only tv" media type rule with Windows line endings' => [
                "\r\n@media\r\nonly\r\ntv\r\n{\r\np\r\n{\r\ncolor\r\n:\r\n#000\r\n;\r\n}\r\n}\r\n",
                '#000',
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $unneededCss
     * @param string $markerNotExpectedInHtml
     *
     * @dataProvider unneededCssThingsDataProvider
     */
    public function emogrifyFiltersUnneededCssThings($unneededCss, $markerNotExpectedInHtml)
    {
        $this->subject->setHtml('<html><p>foo</p></html>');
        $this->subject->setCss($unneededCss);

        $result = $this->subject->emogrify();

        self::assertNotContains($markerNotExpectedInHtml, $result);
    }

    /**
     * @test
     *
     * @param string $unneededCss
     *
     * @dataProvider unneededCssThingsDataProvider
     */
    public function emogrifyMatchesRuleAfterUnneededCssThing($unneededCss)
    {
        $this->subject->setHtml('<html><body></body></html>');
        $this->subject->setCss($unneededCss . ' body { color: green; }');

        $result = $this->subject->emogrify();

        self::assertContains('<body style="color: green;">', $result);
    }

    /**
     * Data provider for media rules.
     *
     * @return string[][]
     */
    public function mediaRulesDataProvider()
    {
        return [
            'style in "only all" media type rule' => ['@media only all {p {color: #000;}}'],
            'style in "only screen" media type rule' => ['@media only screen {p {color: #000;}}'],
            'style in "only screen" media type rule with extra spaces'
            => ['  @media  only  screen  {  p  {  color  :  #000;  }  }  '],
            'style in "only screen" media type rule with linefeeds'
            => ["\n@media\nonly\nscreen\n{\np\n{\ncolor\n:\n#000;\n}\n}\n"],
            'style in "only screen" media type rule with Windows line endings'
            => ["\r\n@media\r\nonly\r\nscreen\r\n{\r\np\r\n{\r\ncolor\r\n:\r\n#000;\r\n}\r\n}\r\n"],
            'style in media type rule' => ['@media {p {color: #000;}}'],
            'style in media type rule with extra spaces' => ['  @media  {  p  {  color  :  #000;  }  }  '],
            'style in media type rule with linefeeds' => ["\n@media\n{\np\n{\ncolor\n:\n#000;\n}\n}\n"],
            'style in media type rule with Windows line endings'
            => ["\r\n@media\r\n{\r\np\r\n{\r\ncolor\r\n:\r\n#000;\r\n}\r\n}\r\n"],
            'style in "screen" media type rule' => ['@media screen {p {color: #000;}}'],
            'style in "screen" media type rule with extra spaces'
            => ['  @media  screen  {  p  {  color  :  #000;  }  }  '],
            'style in "screen" media type rule with linefeeds'
            => ["\n@media\nscreen\n{\np\n{\ncolor\n:\n#000;\n}\n}\n"],
            'style in "screen" media type rule with Windows line endings'
            => ["\r\n@media\r\nscreen\r\n{\r\np\r\n{\r\ncolor\r\n:\r\n#000;\r\n}\r\n}\r\n"],
            'style in "print" media type rule' => ['@media print {p {color: #000;}}'],
            'style in "all" media type rule' => ['@media all {p {color: #000;}}'],
        ];
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider mediaRulesDataProvider
     */
    public function emogrifyKeepsMediaRules($css)
    {
        $this->subject->setHtml('<html><p>foo</p></html>');
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContainsCss($css, $result);
    }

    /**
     * @return string[][]
     */
    public function orderedRulesAndSurroundingCssDataProvider()
    {
        $possibleSurroundingCss = [
            'nothing' => '',
            'space' => ' ',
            'linefeed' => "\n",
            'Windows line ending' => "\r\n",
            'comment' => '/* hello */',
            'other non-matching CSS' => 'h6 { color: #f00; }',
            'other matching CSS' => 'p { color: #f00; }',
            'disallowed media rule' => '@media tv { p { color: #f00; } }',
            'allowed but non-matching media rule' => '@media screen { h6 { color: #f00; } }',
            'non-matching CSS with pseudo-component' => 'h6:hover { color: #f00; }',
        ];
        $possibleCssBefore = $possibleSurroundingCss + [
                '@import' => '@import "foo.css";',
                '@charset' => '@charset "UTF-8";',
            ];

        $datasetsSurroundingCss = [];
        foreach ($possibleCssBefore as $descriptionBefore => $cssBefore) {
            foreach ($possibleSurroundingCss as $descriptionBetween => $cssBetween) {
                foreach ($possibleSurroundingCss as $descriptionAfter => $cssAfter) {
                    // every combination would be a ridiculous c.1000 datasets - choose a select few
                    // test all possible CSS before once
                    if (($cssBetween === '' && $cssAfter === '')
                        // test all possible CSS between once
                        || ($cssBefore === '' && $cssAfter === '')
                        // test all possible CSS after once
                        || ($cssBefore === '' && $cssBetween === '')
                        // test with each possible CSS in all three positions
                        || ($cssBefore === $cssBetween && $cssBetween === $cssAfter)
                    ) {
                        $description = ' with ' . $descriptionBefore . ' before, '
                            . $descriptionBetween . ' between, '
                            . $descriptionAfter . ' after';
                        $datasetsSurroundingCss[$description] = [$cssBefore, $cssBetween, $cssAfter];
                    }
                }
            }
        }

        $datasets = [];
        foreach ($datasetsSurroundingCss as $description => $datasetSurroundingCss) {
            $datasets += [
                'two media rules' . $description => \array_merge(
                    ['@media all { p { color: #333; } }', '@media print { p { color: #000; } }'],
                    $datasetSurroundingCss
                ),
                'two rules involving pseudo-components' . $description => \array_merge(
                    ['a:hover { color: blue; }', 'a:active { color: green; }'],
                    $datasetSurroundingCss
                ),
                'media rule followed by rule involving pseudo-components' . $description => \array_merge(
                    ['@media screen { p { color: #000; } }', 'a:hover { color: green; }'],
                    $datasetSurroundingCss
                ),
                'rule involving pseudo-components followed by media rule' . $description => \array_merge(
                    ['a:hover { color: green; }', '@media screen { p { color: #000; } }'],
                    $datasetSurroundingCss
                ),
            ];
        }
        return $datasets;
    }

    /**
     * @test
     *
     * @param string $rule1
     * @param string $rule2
     * @param string $cssBefore CSS to insert before the first rule
     * @param string $cssBetween CSS to insert between the rules
     * @param string $cssAfter CSS to insert after the second rule
     *
     * @dataProvider orderedRulesAndSurroundingCssDataProvider
     */
    public function emogrifyKeepsRulesCopiedToStyleElementInSpecifiedOrder(
        $rule1,
        $rule2,
        $cssBefore,
        $cssBetween,
        $cssAfter
    ) {
        $this->subject->setHtml('<html><p><a>foo</a></p></html>');
        $this->subject->setCss($cssBefore . $rule1 . $cssBetween . $rule2 . $cssAfter);

        $result = $this->subject->emogrify();

        self::assertContainsCss($rule1 . $rule2, $result);
    }

    /**
     * @test
     */
    public function removeAllowedMediaTypeRemovesStylesForTheGivenMediaType()
    {
        $css = '@media screen { html { some-property: value; } }';
        $this->subject->setHtml('<html></html>');
        $this->subject->setCss($css);
        $this->subject->removeAllowedMediaType('screen');

        $result = $this->subject->emogrify();

        self::assertNotContains('@media', $result);
    }

    /**
     * @test
     */
    public function addAllowedMediaTypeKeepsStylesForTheGivenMediaType()
    {
        $css = '@media braille { html { some-property: value; } }';
        $this->subject->setHtml('<html></html>');
        $this->subject->setCss($css);
        $this->subject->addAllowedMediaType('braille');

        $result = $this->subject->emogrify();

        self::assertContainsCss($css, $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsExistingHeadElementContent()
    {
        $this->subject->setHtml('<html><head><!-- original content --></head></html>');
        $this->subject->setCss('@media all { html { some-property: value; } }');

        $result = $this->subject->emogrify();

        self::assertContains('<!-- original content -->', $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsExistingStyleElementWithMedia()
    {
        $html = $this->html5DocumentType . '<html><head><!-- original content --></head><body></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss('@media all { html { some-property: value; } }');

        $result = $this->subject->emogrify();

        self::assertContains('<style type="text/css">', $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsExistingStyleElementWithMediaInHead()
    {
        $style = '<style type="text/css">@media all { html {  color: red; } }</style>';
        $html = '<html><head>' . $style . '</head><body></body></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertRegExp('/<head>.*<style.*<\\/head>/s', $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsExistingStyleElementWithMediaOutOfBody()
    {
        $style = '<style type="text/css">@media all { html {  color: red; } }</style>';
        $html = '<html><head>' . $style . '</head><body></body></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertNotRegExp('/<body>.*<style/s', $result);
    }

    /**
     * Valid media query which need to be preserved
     *
     * @return string[][]
     */
    public function validMediaPreserveDataProvider()
    {
        return [
            'style in "only screen and size" media type rule' => [
                '@media only screen and (min-device-width: 320px) and (max-device-width: 480px) { h1 { color:red; } }',
            ],
            'style in "screen size" media type rule' => [
                '@media screen and (min-device-width: 320px) and (max-device-width: 480px) { h1 { color:red; } }',
            ],
            'style in "only screen and screen size" media type rule' => [
                '@media only screen and (min-device-width: 320px) and (max-device-width: 480px) { h1 { color:red; } }',
            ],
            'style in "all and screen size" media type rule' => [
                '@media all and (min-device-width: 320px) and (max-device-width: 480px) { h1 { color:red; } }',
            ],
            'style in "only all and" media type rule' => [
                '@media only all and (min-device-width: 320px) and (max-device-width: 480px) { h1 { color:red; } }',
            ],
            'style in "all" media type rule' => ['@media all {p {color: #000;}}'],
            'style in "only screen" media type rule' => ['@media only screen { h1 { color:red; } }'],
            'style in "only all" media type rule' => ['@media only all { h1 { color:red; } }'],
            'style in "screen" media type rule' => ['@media screen { h1 { color:red; } }'],
            'style in "print" media type rule' => ['@media print { * { color:#000 !important; } }'],
            'style in media type rule without specification' => ['@media { h1 { color:red; } }'],
            'style with multiple media type rules' => [
                '@media all { p { color: #000; } }' .
                '@media only screen { h1 { color:red; } }' .
                '@media only all { h1 { color:red; } }' .
                '@media print { * { color:#000 !important; } }' .
                '@media { h1 { color:red; } }',
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider validMediaPreserveDataProvider
     */
    public function emogrifyWithValidMediaQueryContainsInnerCss($css)
    {
        $this->subject->setHtml('<html><h1></h1><p></p></html>');
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContainsCss('<style type="text/css">' . $css . '</style>', $result);
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider validMediaPreserveDataProvider
     */
    public function emogrifyWithValidMinifiedMediaQueryContainsInnerCss($css)
    {
        // Minify CSS by removing unnecessary whitespace.
        $css = \preg_replace('/\\s*{\\s*/', '{', $css);
        $css = \preg_replace('/;?\\s*}\\s*/', '}', $css);
        $css = \preg_replace('/@media{/', '@media {', $css);

        $this->subject->setHtml('<html><h1></h1><p></p></html>');
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains('<style type="text/css">' . $css . '</style>', $result);
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider validMediaPreserveDataProvider
     */
    public function emogrifyForHtmlWithValidMediaQueryContainsInnerCss($css)
    {
        $this->subject->setHtml('<html><style type="text/css">' . $css . '</style><h1></h1><p></p></html>');

        $result = $this->subject->emogrify();

        self::assertContainsCss('<style type="text/css">' . $css . '</style>', $result);
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider validMediaPreserveDataProvider
     */
    public function emogrifyWithValidMediaQueryNotContainsInlineCss($css)
    {
        $this->subject->setHtml('<html><h1></h1></html>');
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertNotContains('style=', $result);
    }

    /**
     * Invalid media query which need to be strip
     *
     * @return string[][]
     */
    public function invalidMediaPreserveDataProvider()
    {
        return [
            'style in "braille" type rule' => ['@media braille { h1 { color:red; } }'],
            'style in "embossed" type rule' => ['@media embossed { h1 { color:red; } }'],
            'style in "handheld" type rule' => ['@media handheld { h1 { color:red; } }'],
            'style in "projection" type rule' => ['@media projection { h1 { color:red; } }'],
            'style in "speech" type rule' => ['@media speech { h1 { color:red; } }'],
            'style in "tty" type rule' => ['@media tty { h1 { color:red; } }'],
            'style in "tv" type rule' => ['@media tv { h1 { color:red; } }'],
        ];
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider invalidMediaPreserveDataProvider
     */
    public function emogrifyWithInvalidMediaQueryNotContainsInnerCss($css)
    {
        $this->subject->setHtml('<html><h1></h1></html>');
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertNotContainsCss($css, $result);
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider invalidMediaPreserveDataProvider
     */
    public function emogrifyWithInvalidMediaQueryNotContainsInlineCss($css)
    {
        $this->subject->setHtml('<html><h1></h1></html>');
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertNotContains('style=', $result);
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider invalidMediaPreserveDataProvider
     */
    public function emogrifyFromHtmlWithInvalidMediaQueryNotContainsInnerCss($css)
    {
        $this->subject->setHtml('<html><style type="text/css">' . $css . '</style><h1></h1></html>');

        $result = $this->subject->emogrify();

        self::assertNotContainsCss($css, $result);
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider invalidMediaPreserveDataProvider
     */
    public function emogrifyFromHtmlWithInvalidMediaQueryNotContainsInlineCss($css)
    {
        $this->subject->setHtml('<html><style type="text/css">' . $css . '</style><h1></h1></html>');

        $result = $this->subject->emogrify();

        self::assertNotContains('style=', $result);
    }

    /**
     * @test
     */
    public function emogrifyIgnoresEmptyMediaQuery()
    {
        $this->subject->setHtml('<html><h1></h1></html>');
        $this->subject->setCss('@media screen {} @media tv { h1 { color: red; } }');

        $result = $this->subject->emogrify();

        self::assertNotContains('style=', $result);
        self::assertNotContains('@media screen', $result);
    }

    /**
     * @test
     */
    public function emogrifyIgnoresMediaQueryWithWhitespaceOnly()
    {
        $this->subject->setHtml('<html><h1></h1></html>');
        $this->subject->setCss('@media screen { } @media tv { h1 { color: red; } }');

        $result = $this->subject->emogrify();

        self::assertNotContains('style=', $result);
        self::assertNotContains('@media screen', $result);
    }

    /**
     * @return string[][]
     */
    public function mediaTypeDataProvider()
    {
        return [
            'disallowed type' => ['tv'],
            'allowed type' => ['screen'],
        ];
    }

    /**
     * @test
     *
     * @param string $emptyRuleMediaType
     *
     * @dataProvider mediaTypeDataProvider
     */
    public function emogrifyKeepsMediaRuleAfterEmptyMediaRule($emptyRuleMediaType)
    {
        $this->subject->setHtml('<html><h1></h1></html>');
        $this->subject->setCss('@media ' . $emptyRuleMediaType . ' {} @media all { h1 { color: red; } }');

        $result = $this->subject->emogrify();

        self::assertContainsCss('@media all { h1 { color: red; } }', $result);
    }

    /**
     * @test
     *
     * @param string $emptyRuleMediaType
     *
     * @dataProvider mediaTypeDataProvider
     */
    public function emogrifyNotKeepsUnneededMediaRuleAfterEmptyMediaRule($emptyRuleMediaType)
    {
        $this->subject->setHtml('<html><h1></h1></html>');
        $this->subject->setCss('@media ' . $emptyRuleMediaType . ' {} @media speech { h1 { color: red; } }');

        $result = $this->subject->emogrify();

        self::assertNotContains('@media', $result);
    }

    /**
     * @param string[] $precedingSelectorComponents Array of selectors to which each type of pseudo-component is
     *                                              appended to create a selector for a CSS rule.
     *                                              Keys are human-readable descriptions.
     *
     * @return string[][]
     */
    private function getCssRuleDatasetsWithSelectorPseudoComponents(array $precedingSelectorComponents)
    {
        $rulesComponents = [
            'pseudo-element' => [
                'selectorPseudoComponent' => '::after',
                'declarationsBlock' => 'content: "bar";',
            ],
            'CSS2 pseudo-element' => [
                'selectorPseudoComponent' => ':after',
                'declarationsBlock' => 'content: "bar";',
            ],
            'hyphenated pseudo-element' => [
                'selectorPseudoComponent' => '::first-letter',
                'declarationsBlock' => 'color: green;',
            ],
            'pseudo-class' => [
                'selectorPseudoComponent' => ':hover',
                'declarationsBlock' => 'color: green;',
            ],
            'hyphenated pseudo-class' => [
                'selectorPseudoComponent' => ':read-only',
                'declarationsBlock' => 'color: green;',
            ],
            'pseudo-class with parameter' => [
                'selectorPseudoComponent' => ':lang(en)',
                'declarationsBlock' => 'color: green;',
            ],
            'not with pseudo-class' => [
                'selectorPseudoComponent' => ':not(:hover)',
                'declarationsBlock' => 'color: green;',
            ],
            'nested not with pseudo-class' => [
                'selectorPseudoComponent' => ':not(:not(:hover))',
                'declarationsBlock' => 'color: green;',
            ],
        ];

        $datasets = [];
        foreach ($precedingSelectorComponents as $precedingComponentDescription => $precedingSelectorComponent) {
            foreach ($rulesComponents as $pseudoComponentDescription => $ruleComponents) {
                $datasets[$precedingComponentDescription . ' ' . $pseudoComponentDescription] = [
                    $precedingSelectorComponent . $ruleComponents['selectorPseudoComponent']
                    . ' { ' . $ruleComponents['declarationsBlock'] . ' }',
                ];
            }
        }
        return $datasets;
    }

    /**
     * @return string[][]
     */
    public function matchingSelectorWithPseudoComponentCssRuleDataProvider()
    {
        $datasetsWithSelectorPseudoComponents = $this->getCssRuleDatasetsWithSelectorPseudoComponents(
            [
                'lone' => '',
                'type &' => 'a',
                'class &' => '.a',
                'ID &' => '#a',
                'attribute &' => 'a[href="a"]',
                'static pseudo-class &' => 'a:first-child',
                'ancestor &' => 'p ',
                'ancestor & type &' => 'p a',
            ]
        );
        $datasetsWithCombinedPseudoSelectors = [
            'pseudo-class & descendant' => ['p:hover a { color: green; }'],
            'pseudo-class & pseudo-element' => ['a:hover::after { content: "bar"; }'],
            'pseudo-element & pseudo-class' => ['a::after:hover { content: "bar"; }'],
            'two pseudo-classes' => ['a:focus:hover { color: green; }'],
            'dynamic and static pseudo-classes' => ['a:hover:first-child { color: green; }'],
            'static and dynamic pseudo-classes' => ['a:first-child:hover { color: green; }'],
        ];
        $datasetsWithUnsupportedStaticPseudoClasses = [
            ':any-link' => ['a:any-link { color: green; }'],
            ':empty' => ['a:empty { color: green; }'],
            ':first-of-type' => ['a:first-of-type { color: green; }'],
            ':last-of-type' => ['a:last-of-type { color: green; }'],
            ':nth-last-child' => ['a:nth-last-child(2n+1) { color: green; }'],
            ':nth-last-of-type' => ['a:nth-last-of-type(2n+1) { color: green; }'],
            ':only-child' => ['a:only-child { color: green; }'],
            ':only-of-type' => ['a:only-of-type { color: green; }'],
        ];

        return \array_merge(
            $datasetsWithSelectorPseudoComponents,
            $datasetsWithCombinedPseudoSelectors,
            $datasetsWithUnsupportedStaticPseudoClasses
        );
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider matchingSelectorWithPseudoComponentCssRuleDataProvider
     */
    public function emogrifyKeepsRuleWithPseudoComponentInMatchingSelector($css)
    {
        $this->subject->setHtml('<html><p><a id="a" class="a" href="a">foo</a></p></html>');
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContainsCss($css, $result);
    }

    /**
     * @return string[][]
     */
    public function nonMatchingSelectorWithPseudoComponentCssRuleDataProvider()
    {
        $datasetsWithSelectorPseudoComponents = $this->getCssRuleDatasetsWithSelectorPseudoComponents(
            [
                'type &' => 'b',
                'class &' => '.b',
                'ID &' => '#b',
                'attribute &' => 'a[href="b"]',
                'static pseudo-class &' => 'a:not(.a)',
                'ancestor &' => 'ul ',
                'ancestor & type &' => 'p b',
            ]
        );
        $datasetsWithCombinedPseudoSelectors = [
            'pseudo-class & descendant' => ['ul:hover a { color: green; }'],
            'pseudo-class & pseudo-element' => ['b:hover::after { content: "bar"; }'],
            'pseudo-element & pseudo-class' => ['b::after:hover { content: "bar"; }'],
            'two pseudo-classes' => ['input:focus:hover { color: green; }'],
        ];

        return \array_merge($datasetsWithSelectorPseudoComponents, $datasetsWithCombinedPseudoSelectors);
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider nonMatchingSelectorWithPseudoComponentCssRuleDataProvider
     */
    public function emogrifyNotKeepsRuleWithPseudoComponentInNonMatchingSelector($css)
    {
        $this->subject->setHtml('<html><p><a id="a" class="a" href="#">foo</a></p></html>');
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertNotContainsCss($css, $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsRuleInMediaQueryWithPseudoComponentInMatchingSelector()
    {
        $this->subject->setHtml('<html><a>foo</a></html>');
        $css = '@media screen { a:hover { color: green; } }';
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContainsCss($css, $result);
    }

    /**
     * @test
     */
    public function emogrifyNotKeepsRuleInMediaQueryWithPseudoComponentInNonMatchingSelector()
    {
        $this->subject->setHtml('<html><a>foo</a></html>');
        $css = '@media screen { b:hover { color: green; } }';
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertNotContainsCss($css, $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsRuleWithPseudoComponentInMultipleMatchingSelectorsFromSingleRule()
    {
        $this->subject->setHtml('<html><p>foo</p><a>bar</a></html>');
        $css = 'p:hover, a:hover { color: green; }';
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContainsCss($css, $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsOnlyMatchingSelectorsWithPseudoComponentFromSingleRule()
    {
        $this->subject->setHtml('<html><a>foo</a></html>');
        $this->subject->setCss('p:hover, a:hover { color: green; }');

        $result = $this->subject->emogrify();

        self::assertContainsCss('<style type="text/css">a:hover { color: green; }</style>', $result);
    }

    /**
     * @test
     */
    public function emogrifyAppliesCssToMatchingElementsAndKeepsRuleWithPseudoComponentFromSingleRule()
    {
        $this->subject->setHtml('<html><p>foo</p><a>bar</a></html>');
        $this->subject->setCss('p, a:hover { color: green; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="color: green;">', $result);
        self::assertContainsCss('<style type="text/css">a:hover { color: green; }</style>', $result);
    }

    /**
     * @return string[][]
     */
    public function mediaTypesDataProvider()
    {
        return [
            'disallowed type after disallowed type' => ['tv', 'speech'],
            'allowed type after disallowed type' => ['tv', 'all'],
            'disallowed type after allowed type' => ['screen', 'tv'],
            'allowed type after allowed type' => ['screen', 'all'],
        ];
    }

    /**
     * @test
     *
     * @param string $emptyRuleMediaType
     * @param string $mediaType
     *
     * @dataProvider mediaTypesDataProvider
     */
    public function emogrifyAppliesCssBetweenEmptyMediaRuleAndMediaRule($emptyRuleMediaType, $mediaType)
    {
        $this->subject->setHtml('<html><h1></h1></html>');
        $this->subject->setCss(
            '@media ' . $emptyRuleMediaType . ' {} h1 { color: green; } @media ' . $mediaType
            . ' { h1 { color: red; } }'
        );

        $result = $this->subject->emogrify();

        self::assertContains('<h1 style="color: green;">', $result);
    }

    /**
     * @test
     *
     * @param string $emptyRuleMediaType
     * @param string $mediaType
     *
     * @dataProvider mediaTypesDataProvider
     */
    public function emogrifyAppliesCssBetweenEmptyMediaRuleAndMediaRuleWithCssAfter($emptyRuleMediaType, $mediaType)
    {
        $this->subject->setHtml('<html><h1></h1></html>');
        $this->subject->setCss(
            '@media ' . $emptyRuleMediaType . ' {} h1 { color: green; } @media ' . $mediaType
            . ' { h1 { color: red; } } h1 { font-size: 24px; }'
        );

        $result = $this->subject->emogrify();

        self::assertContains('<h1 style="color: green; font-size: 24px;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyAppliesCssFromStyleNodes()
    {
        $styleAttributeValue = 'color: #ccc;';
        $this->subject->setHtml('<html><style type="text/css">html {' . $styleAttributeValue . '}</style></html>');

        $result = $this->subject->emogrify();

        self::assertContains('<html style="' . $styleAttributeValue . '">', $result);
    }

    /**
     * @test
     */
    public function emogrifyWhenDisabledNotAppliesCssFromStyleBlocks()
    {
        $styleAttributeValue = 'color: #ccc;';
        $this->subject->setHtml('<html><style type="text/css">html {' . $styleAttributeValue . '}</style></html>');
        $this->subject->disableStyleBlocksParsing();

        $result = $this->subject->emogrify();

        self::assertNotContains('style=', $result);
    }

    /**
     * @test
     */
    public function emogrifyWhenStyleBlocksParsingDisabledKeepInlineStyles()
    {
        $styleAttributeValue = 'text-align: center;';
        $this->subject->setHtml(
            '<html><head><style type="text/css">p { color: #ccc; }</style></head>' .
            '<body><p style="' . $styleAttributeValue . '">paragraph</p></body></html>'
        );
        $this->subject->disableStyleBlocksParsing();

        $result = $this->subject->emogrify();

        self::assertContains('<p style="' . $styleAttributeValue . '">', $result);
    }

    /**
     * @test
     */
    public function emogrifyWhenDisabledNotAppliesCssFromInlineStyles()
    {
        $this->subject->setHtml('<html style="color: #ccc;"></html>');
        $this->subject->disableInlineStyleAttributesParsing();

        $result = $this->subject->emogrify();

        self::assertNotContains('<html style', $result);
    }

    /**
     * @test
     */
    public function emogrifyWhenInlineStyleAttributesParsingDisabledKeepStyleBlockStyles()
    {
        $styleAttributeValue = 'color: #ccc;';
        $this->subject->setHtml(
            '<html><head><style type="text/css">p { ' . $styleAttributeValue . ' }</style></head>' .
            '<body><p style="text-align: center;">paragraph</p></body></html>'
        );
        $this->subject->disableInlineStyleAttributesParsing();

        $result = $this->subject->emogrify();

        self::assertContains('<p style="' . $styleAttributeValue . '">', $result);
    }

    /**
     * Emogrify was handling case differently for passed-in CSS vs. CSS parsed from style blocks.
     *
     * @test
     */
    public function emogrifyAppliesCssWithMixedCaseAttributesInStyleBlock()
    {
        $this->subject->setHtml(
            '<html><head><style>#topWrap p {padding-bottom: 1px;PADDING-TOP: 0;}</style></head>' .
            '<body><div id="topWrap"><p style="text-align: center;">some content</p></div></body></html>'
        );

        $result = $this->subject->emogrify();

        self::assertContains('<p style="padding-bottom: 1px; padding-top: 0; text-align: center;">', $result);
    }

    /**
     * Style block CSS overrides values.
     *
     * @test
     */
    public function emogrifyMergesCssWithMixedCaseAttribute()
    {
        $this->subject->setHtml(
            '<html><head><style>#topWrap p {padding-bottom: 3px;PADDING-TOP: 1px;}</style></head>' .
            '<body><div id="topWrap"><p style="text-align: center;">some content</p></div></body></html>'
        );
        $this->subject->setCss('p { margin: 0; padding-TOP: 0; PADDING-bottom: 1PX;}');

        $result = $this->subject->emogrify();

        self::assertContains(
            '<p style="margin: 0; padding-bottom: 3px; padding-top: 1px; text-align: center;">',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyMergesCssWithMixedUnits()
    {
        $this->subject->setHtml(
            '<html><head><style>#topWrap p {margin:0;padding-bottom: 1px;}</style></head>' .
            '<body><div id="topWrap"><p style="text-align: center;">some content</p></div></body></html>'
        );
        $this->subject->setCss('p { margin: 1px; padding-bottom:0;}');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 0; padding-bottom: 1px; text-align: center;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsCssMediaQueriesWithCssCommentAfterMediaQuery()
    {
        $this->subject->setHtml('<html><body></body></html>');
        $this->subject->setCss(
            '@media only screen and (max-width: 480px) { body { color: #ffffff } /* some comment */ }'
        );

        $result = $this->subject->emogrify();

        self::assertContains('@media only screen and (max-width: 480px)', $result);
    }

    /**
     * @return string[][]
     */
    public function xmlSelfClosingTagDataProvider()
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
     */
    public function nonXmlSelfClosingTagDataProvider()
    {
        return \array_map(
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
     *         - The name of a self-closing tag contained in the HTML (e.g. "br").
     *         - The equivalent HTML with XML self-closing tags (e.g. "...<br/>...");
     */
    public function selfClosingTagDataProvider()
    {
        return \array_map(
            static function (array $dataset) {
                return [
                    \str_replace('/>', '>', $dataset[0]),
                    $dataset[1],
                    $dataset[0],
                ];
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
     * @return string[][] The new datasets comprise the first dataset from the left-hand side with each of the datasets
     * from the right-hand side, and the each of the remaining datasets from the left-hand side with the first dataset
     * from the right-hand side.
     */
    public static function joinDatasets(array $leftDatasets, array $rightDatasets)
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
    public function documentTypeAndSelfClosingTagDataProvider()
    {
        return self::joinDatasets($this->documentTypeDataProvider(), $this->selfClosingTagDataProvider());
    }

    /**
     * @test
     *
     * @param string $documentType
     * @param string $htmlWithNonXmlSelfClosingTags
     * @param string $tagName
     * @param string $htmlWithXmlSelfClosingTags
     *
     * @dataProvider documentTypeAndSelfClosingTagDataProvider
     */
    public function emogrifyConvertsXmlSelfClosingTagsToNonXmlSelfClosingTag(
        $documentType,
        $htmlWithNonXmlSelfClosingTags,
        $tagName,
        $htmlWithXmlSelfClosingTags
    ) {
        $this->subject->removeUnprocessableHtmlTag($tagName);
        $this->subject->setHtml(
            $documentType . '<html><body>' . $htmlWithXmlSelfClosingTags . '</body></html>'
        );

        $result = $this->subject->emogrify();

        self::assertContains('<body>' . $htmlWithNonXmlSelfClosingTags . '</body>', $result);
    }

    /**
     * @test
     *
     * @param string $documentType
     * @param string $htmlWithNonXmlSelfClosingTags
     * @param string $tagName
     *
     * @dataProvider documentTypeAndSelfClosingTagDataProvider
     */
    public function emogrifyKeepsNonXmlSelfClosingTags($documentType, $htmlWithNonXmlSelfClosingTags, $tagName)
    {
        $this->subject->removeUnprocessableHtmlTag($tagName);
        $this->subject->setHtml(
            $documentType . '<html><body>' . $htmlWithNonXmlSelfClosingTags . '</body></html>'
        );

        $result = $this->subject->emogrify();

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
    public function emogrifyNotAddsClosingTagForSelfClosingTags($htmlWithNonXmlSelfClosingTags, $tagName)
    {
        $this->subject->removeUnprocessableHtmlTag($tagName);
        $this->subject->setHtml(
            '<html><body>' . $htmlWithNonXmlSelfClosingTags . '</body></html>'
        );

        $result = $this->subject->emogrify();

        self::assertNotContains('</' . $tagName, $result);
    }

    /**
     * @test
     *
     * @param string $htmlWithNonXmlSelfClosingTags
     * @param string $tagName
     *
     * @dataProvider nonXmlSelfClosingTagDataProvider
     */
    public function getDomDocumentVoidElementNotHasChildNodes($htmlWithNonXmlSelfClosingTags, $tagName)
    {
        // Append a 'trap' element that might become a child node if the HTML is parsed incorrectly
        $this->subject->setHtml(
            '<html><body>' . $htmlWithNonXmlSelfClosingTags . '<span>foo</span></body></html>'
        );

        $domDocument = $this->subject->getDomDocument();

        $voidElements = $domDocument->getElementsByTagName($tagName);
        foreach ($voidElements as $element) {
            self::assertFalse($element->hasChildNodes());
        }
    }

    /**
     * @test
     */
    public function emogrifyAutomaticallyClosesUnclosedTag()
    {
        $this->subject->setHtml('<html><body><p></body></html>');

        $result = $this->subject->emogrify();

        self::assertContains('<body><p></p></body>', $result);
    }

    /**
     * @test
     */
    public function emogrifyReturnsCompleteHtmlDocument()
    {
        $this->subject->setHtml('<html><body><p></p></body></html>');

        $result = $this->subject->emogrify();

        self::assertSame(
            $this->html5DocumentType . "\n" .
            "<html>\n" .
            '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>' . "\n" .
            "<body><p></p></body>\n" .
            "</html>\n",
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyBodyContentReturnsBodyContentFromHtml()
    {
        $this->subject->setHtml('<html><body><p></p></body></html>');

        $result = $this->subject->emogrifyBodyContent();

        self::assertSame('<p></p>', $result);
    }

    /**
     * @test
     */
    public function emogrifyBodyContentReturnsBodyContentFromPartialContent()
    {
        $this->subject->setHtml('<p></p>');

        $result = $this->subject->emogrifyBodyContent();

        self::assertSame('<p></p>', $result);
    }

    /**
     * Issue #677
     *
     * @test
     */
    public function emogrifyBodyContentWithCssRuleForBodyReturnsBodyContent()
    {
        $bodyContent = '<div>simple</div>';
        $this->subject->setHtml($bodyContent);
        $this->subject->setCss('body { max-width: 100%; }');

        $result = $this->subject->emogrifyBodyContent();

        self::assertSame($bodyContent, $result);
    }

    /**
     * @test
     *
     * @param string $htmlWithNonXmlSelfClosingTags
     * @param string $tagName
     *
     * @dataProvider nonXmlSelfClosingTagDataProvider
     */
    public function emogrifyBodyContentNotAddsClosingTagForSelfClosingTags($htmlWithNonXmlSelfClosingTags, $tagName)
    {
        $this->subject->removeUnprocessableHtmlTag($tagName);
        $this->subject->setHtml(
            '<html><body>' . $htmlWithNonXmlSelfClosingTags . '</body></html>'
        );

        $result = $this->subject->emogrifyBodyContent();

        self::assertNotContains('</' . $tagName, $result);
    }

    /**
     * Sets HTML of subject to boilerplate HTML with a single `<p>` in `<body>` and empty `<head>`
     *
     * @param string $style Optional value for the style attribute of the `<p>` element
     *
     * @return void
     */
    private function setSubjectBoilerplateHtml($style = '')
    {
        $html = '<html><head></head><body><p';
        if ($style !== '') {
            $html .= ' style="' . $style . '"';
        }
        $html .= '>some content</p></body></html>';
        $this->subject->setHtml($html);
    }

    /**
     * @test
     */
    public function importantInExternalCssOverwritesInlineCss()
    {
        $this->setSubjectBoilerplateHtml('margin: 2px;');
        $this->subject->setCss('p { margin: 1px !important; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 1px;">', $result);
    }

    /**
     * @test
     */
    public function importantInExternalCssKeepsInlineCssForOtherAttributes()
    {
        $this->setSubjectBoilerplateHtml('margin: 2px; text-align: center;');
        $this->subject->setCss('p { margin: 1px !important; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="text-align: center; margin: 1px;">', $result);
    }

    /**
     * @test
     */
    public function importantIsCaseInsensitive()
    {
        $this->setSubjectBoilerplateHtml('margin: 2px;');
        $this->subject->setCss('p { margin: 1px !ImPorTant; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 1px !ImPorTant;">', $result);
    }

    /**
     * @test
     */
    public function secondImportantStyleOverwritesFirstOne()
    {
        $this->setSubjectBoilerplateHtml();
        $this->subject->setCss('p { margin: 1px !important; } p { margin: 2px !important; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 2px;">', $result);
    }

    /**
     * @test
     */
    public function secondNonImportantStyleOverwritesFirstOne()
    {
        $this->setSubjectBoilerplateHtml();
        $this->subject->setCss('p { margin: 1px; } p { margin: 2px; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 2px;">', $result);
    }

    /**
     * @test
     */
    public function secondNonImportantStyleNotOverwritesFirstImportantOne()
    {
        $this->setSubjectBoilerplateHtml();
        $this->subject->setCss('p { margin: 1px !important; } p { margin: 2px; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 1px;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyAppliesLaterShorthandStyleAfterIndividualStyle()
    {
        $this->setSubjectBoilerplateHtml();
        $this->subject->setCss('p { margin-top: 1px; } p { margin: 2px; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin-top: 1px; margin: 2px;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyAppliesLaterOverridingStyleAfterStyleAfterOverriddenStyle()
    {
        $this->setSubjectBoilerplateHtml();
        $this->subject->setCss('p { margin-top: 1px; } p { margin: 2px; } p { margin-top: 3px; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 2px; margin-top: 3px;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyAppliesInlineOverridingStyleAfterCssStyleAfterOverriddenCssStyle()
    {
        $this->setSubjectBoilerplateHtml('margin-top: 3px;');
        $this->subject->setCss('p { margin-top: 1px; } p { margin: 2px; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 2px; margin-top: 3px;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyAppliesLaterInlineOverridingStyleAfterEarlierInlineStyle()
    {
        $this->setSubjectBoilerplateHtml('margin: 2px; margin-top: 3px;');
        $this->subject->setCss('p { margin-top: 1px; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 2px; margin-top: 3px;">', $result);
    }

    /**
     * @test
     */
    public function irrelevantMediaQueriesAreRemoved()
    {
        $uselessQuery = '@media all and (max-width: 500px) { em { color:red; } }';
        $this->subject->setCss($uselessQuery);
        $this->subject->setHtml('<html><body><p></p></body></html>');

        $result = $this->subject->emogrify();

        self::assertNotContains('@media', $result);
    }

    /**
     * @test
     */
    public function relevantMediaQueriesAreRetained()
    {
        $usefulQuery = '@media all and (max-width: 500px) { p { color:red; } }';
        $this->subject->setCss($usefulQuery);
        $this->subject->setHtml('<html><body><p></p></body></html>');

        $result = $this->subject->emogrify();

        self::assertContainsCss($usefulQuery, $result);
    }

    /**
     * @test
     */
    public function importantStyleRuleFromInlineCssOverwritesImportantStyleRuleFromExternalCss()
    {
        $this->setSubjectBoilerplateHtml('margin: 2px !important; text-align: center;');
        $this->subject->setCss('p { margin: 1px !important; padding: 1px;}');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="padding: 1px; text-align: center; margin: 2px;">', $result);
    }

    /**
     * @test
     */
    public function addExcludedSelectorRemovesMatchingElementsFromEmogrification()
    {
        $this->subject->setHtml('<html><body><p class="x"></p></body></html>');
        $this->subject->setCss('p { margin: 0; }');

        $this->subject->addExcludedSelector('p.x');
        $result = $this->subject->emogrify();

        self::assertContains('<p class="x"></p>', $result);
    }

    /**
     * @test
     */
    public function addExcludedSelectorExcludesMatchingElementEventWithWhitespaceAroundSelector()
    {
        $this->subject->setHtml('<html><body><p class="x"></p></body></html>');
        $this->subject->setCss('p { margin: 0; }');

        $this->subject->addExcludedSelector(' p.x ');
        $result = $this->subject->emogrify();

        self::assertContains('<p class="x"></p>', $result);
    }

    /**
     * @test
     */
    public function addExcludedSelectorKeepsNonMatchingElementsInEmogrification()
    {
        $this->subject->setHtml('<html><body><p></p></body></html>');
        $this->subject->setCss('p { margin: 0; }');

        $this->subject->addExcludedSelector('p.x');
        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 0;"></p>', $result);
    }

    /**
     * @test
     */
    public function removeExcludedSelectorGetsMatchingElementsToBeEmogrifiedAgain()
    {
        $this->subject->setHtml('<html><body><p class="x"></p></body></html>');
        $this->subject->setCss('p { margin: 0; }');

        $this->subject->addExcludedSelector('p.x');
        $this->subject->removeExcludedSelector('p.x');

        $result = $this->subject->emogrify();

        self::assertContains('<p class="x" style="margin: 0;"></p>', $result);
    }

    /**
     * @test
     */
    public function emogrifyInDebugModeForInvalidExcludedSelectorThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->setDebug(true);

        $this->subject->setHtml('<html></html>');
        $this->subject->addExcludedSelector('..p');

        $this->subject->emogrify();
    }

    /**
     * @test
     */
    public function emogrifyNotInDebugModeIgnoresInvalidExcludedSelector()
    {
        $this->subject->setDebug(false);

        $this->subject->setHtml('<html><p class="x"></p></html>');
        $this->subject->addExcludedSelector('..p');

        $result = $this->subject->emogrify();

        self::assertContains('<p class="x"></p>', $result);
    }

    /**
     * @test
     */
    public function emogrifyNotInDebugModeIgnoresOnlyInvalidExcludedSelector()
    {
        $this->subject->setDebug(false);

        $this->subject->setHtml('<html><p class="x"></p><p class="y"></p><p class="z"></p></html>');
        $this->subject->setCss('p { color: red };');
        $this->subject->addExcludedSelector('p.x');
        $this->subject->addExcludedSelector('..p');
        $this->subject->addExcludedSelector('p.z');

        $result = $this->subject->emogrify();

        self::assertContains('<p class="x"></p>', $result);
        self::assertContains('<p class="y" style="color: red;"></p>', $result);
        self::assertContains('<p class="z"></p>', $result);
    }

    /**
     * @test
     */
    public function emptyMediaQueriesAreRemoved()
    {
        $emptyQuery = '@media all and (max-width: 500px) { }';
        $this->subject->setCss($emptyQuery);
        $this->subject->setHtml('<html><body><p></p></body></html>');

        $result = $this->subject->emogrify();

        self::assertNotContains('@media', $result);
    }

    /**
     * @test
     */
    public function multiLineMediaQueryWithWindowsLineEndingsIsAppliedOnlyOnce()
    {
        $css = "@media all {\r\n" .
            ".medium {font-size:18px;}\r\n" .
            ".small {font-size:14px;}\r\n" .
            '}';
        $this->subject->setCss($css);
        $this->subject->setHtml(
            '<html><body>' .
            '<p class="medium">medium</p>' .
            '<p class="small">small</p>' .
            '</body></html>'
        );

        $result = $this->subject->emogrify();

        self::assertContainsCssCount(1, $css, $result);
    }

    /**
     * @test
     */
    public function multiLineMediaQueryWithUnixLineEndingsIsAppliedOnlyOnce()
    {
        $css = "@media all {\n" .
            ".medium {font-size:18px;}\n" .
            ".small {font-size:14px;}\n" .
            '}';
        $this->subject->setCss($css);
        $this->subject->setHtml(
            '<html><body>' .
            '<p class="medium">medium</p>' .
            '<p class="small">small</p>' .
            '</body></html>'
        );

        $result = $this->subject->emogrify();

        self::assertContainsCssCount(1, $css, $result);
    }

    /**
     * @test
     */
    public function multipleMediaQueriesAreAppliedOnlyOnce()
    {
        $css = "@media all {\n" .
            ".medium {font-size:18px;}\n" .
            ".small {font-size:14px;}\n" .
            '}' .
            "@media screen {\n" .
            ".medium {font-size:24px;}\n" .
            ".small {font-size:18px;}\n" .
            '}';
        $this->subject->setCss($css);
        $this->subject->setHtml(
            '<html><body>' .
            '<p class="medium">medium</p>' .
            '<p class="small">small</p>' .
            '</body></html>'
        );

        $result = $this->subject->emogrify();

        self::assertContainsCssCount(1, $css, $result);
    }

    /**
     * @return string[][]
     */
    public function dataUriMediaTypeDataProvider()
    {
        return [
            'nothing' => [''],
            ';charset=utf-8' => [';charset=utf-8'],
            ';base64' => [';base64'],
            ';charset=utf-8;base64' => [';charset=utf-8;base64'],
        ];
    }

    /**
     * @test
     *
     * @param string $dataUriMediaType
     *
     * @dataProvider dataUriMediaTypeDataProvider
     */
    public function dataUrisAreConserved($dataUriMediaType)
    {
        $this->subject->setHtml('<html></html>');
        $styleRule = 'background-image: url(data:image/png' . $dataUriMediaType .
            ',iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAIAAAAC64paAAABUk' .
            'lEQVQ4y81UsY6CQBCdWXBjYWFMjEgAE0piY8c38B9+iX+ksaHCgs5YWEhIrJCQYGJBomiC7lzhVcfqEa+5KXfey3s783bRdd00TR' .
            'VFAQAAICJEhN/q8Xjoug7D4RA+qsFgwDjn9QYiTiaT+Xx+OByOx+NqtapjWq0WjEajekPTtCAIiIiIyrKMoqiOMQxDlVqyLMt1XQ' .
            'A4nU6z2Wy9XkthEnK/3zdN8znC/X7v+36WZfJ7120vFos4joUQRHS5XDabzXK5bGrbtu1er/dtTFU1TWu3202VHceZTqe3242Itt' .
            'ut53nj8bip8m6345wLIQCgKIowDIuikAoz6Wm3233mjHPe6XRe5UROJqImIWPwh/pvZMbYM2GKorx5oUw6m+v1miTJ+XzO8/x+v7' .
            '+UtizrM8+GYahVVSFik9/jxy6rqlJN02SM1cmI+GbbQghd178AAO2FXws6LwMAAAAASUVORK5CYII=);';
        $this->subject->setCss('html {' . $styleRule . '}');

        $result = $this->subject->emogrify();

        self::assertContains(
            '<html style="' . $styleRule . '">',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifierIgnoresPseudoClassCombinedWithPseudoElement()
    {
        $this->subject->setHtml('<html><body><div></div></body></html>');
        $this->subject->setCss('div:last-child::after {float: right;}');

        $html = $this->subject->emogrify();

        self::assertContains('<div></div>', $html);
    }

    /**
     * @test
     */
    public function emogrifyKeepsInlineStylePriorityVersusStyleBlockRules()
    {
        $this->subject->setHtml(
            '<html><head><style>p {padding:10px};</style></head><body><p style="padding-left:20px;"></p></body></html>'
        );

        $result = $this->subject->emogrify();

        self::assertContains('<p style="padding: 10px; padding-left: 20px;">', $result);
    }

    /**
     * Asserts that $html contains a $tagName tag with the $attribute attribute.
     *
     * @param string $html the HTML string we are searching in
     * @param string $tagName the HTML tag we are looking for
     * @param string $attribute the attribute we are looking for (with or even without a value)
     */
    private function assertHtmlStringContainsTagWithAttribute($html, $tagName, $attribute)
    {
        self::assertTrue(
            \preg_match('/<' . \preg_quote($tagName, '/') . '[^>]+' . \preg_quote($attribute, '/') . '/', $html) > 0
        );
    }

    /**
     * @return string[][]
     */
    public function cssForImportantRuleRemovalDataProvider()
    {
        return [
            'one !important rule only' => [
                'width: 1px !important',
                'width: 1px;',
            ],
            'multiple !important rules only' => [
                'width: 1px !important; height: 1px !important',
                'width: 1px; height: 1px;',
            ],
            'multiple declarations, one !important rule at the beginning' => [
                'width: 1px !important; height: 1px; color: red',
                'height: 1px; color: red; width: 1px;',
            ],
            'multiple declarations, one !important rule somewhere in the middle' => [
                'height: 1px; width: 1px !important; color: red',
                'height: 1px; color: red; width: 1px;',
            ],
            'multiple declarations, one !important rule at the end' => [
                'height: 1px; color: red; width: 1px !important',
                'height: 1px; color: red; width: 1px;',
            ],
            'multiple declarations, multiple !important rules at the beginning' => [
                'width: 1px !important; height: 1px !important; color: red; float: left',
                'color: red; float: left; width: 1px; height: 1px;',
            ],
            'multiple declarations, multiple consecutive !important rules somewhere in the middle (#1)' => [
                'color: red; width: 1px !important; height: 1px !important; float: left',
                'color: red; float: left; width: 1px; height: 1px;',
            ],
            'multiple declarations, multiple consecutive !important rules somewhere in the middle (#2)' => [
                'color: red; width: 1px !important; height: 1px !important; float: left; clear: both',
                'color: red; float: left; clear: both; width: 1px; height: 1px;',
            ],
            'multiple declarations, multiple not consecutive !important rules somewhere in the middle' => [
                'color: red; width: 1px !important; clear: both; height: 1px !important; float: left',
                'color: red; clear: both; float: left; width: 1px; height: 1px;',
            ],
            'multiple declarations, multiple !important rules at the end' => [
                'color: red; float: left; width: 1px !important; height: 1px !important',
                'color: red; float: left; width: 1px; height: 1px;',
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $originalStyleAttributeContent
     * @param string $expectedStyleAttributeContent
     *
     * @dataProvider cssForImportantRuleRemovalDataProvider
     */
    public function emogrifyRemovesImportantRule($originalStyleAttributeContent, $expectedStyleAttributeContent)
    {
        $this->subject->setHtml(
            '<html><head><body><p style="' . $originalStyleAttributeContent . '"></p></body></html>'
        );

        $result = $this->subject->emogrify();

        self::assertContains('<p style="' . $expectedStyleAttributeContent . '">', $result);
    }

    /**
     * @test
     */
    public function emogrifyInDebugModeForInvalidSelectorsInMediaQueryBlocksThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->setDebug(true);

        $this->subject->setHtml('<html></html>');
        $this->subject->setCss('@media screen {p^^ {color: red;}}');

        $this->subject->emogrify();
    }

    /**
     * @test
     */
    public function emogrifyNotInDebugModeKeepsInvalidOrUnrecognizedSelectorsInMediaQueryBlocks()
    {
        $this->subject->setDebug(false);

        $this->subject->setHtml('<html></html>');
        $css = '@media screen {p^^ {color: red;}}';
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContainsCss($css, $result);
    }

    /**
     * @return string[][]
     */
    public function provideValidImportRules()
    {
        return [
            'single @import' => [
                'before' => '',
                '@import' => '@import "foo.css";',
                'after' => '',
            ],
            'uppercase @IMPORT' => [
                'before' => '',
                '@import' => '@IMPORT "foo.css";',
                'after' => '',
            ],
            'mixed case @ImPoRt' => [
                'before' => '',
                '@import' => '@ImPoRt "foo.css";',
                'after' => '',
            ],
            '2 @imports' => [
                'before' => '',
                '@import' => '@import "foo.css";' . "\n" . '@import "bar.css";',
                'after' => '',
            ],
            '2 @imports, minified' => [
                'before' => '',
                '@import' => '@import "foo.css";@import "bar.css";',
                'after' => '',
            ],
            '@import after @charset' => [
                'before' => '@charset "UTF-8";' . "\n",
                '@import' => '@import "foo.css";',
                'after' => '',
            ],
            '@import followed by matching inlinable rule' => [
                'before' => '',
                '@import' => '@import "foo.css";',
                'after' => "\n" . 'p { color: green; }',
            ],
            '@import followed by matching uninlinable rule' => [
                'before' => '',
                '@import' => '@import "foo.css";',
                'after' => "\n" . 'p:hover { color: green; }',
            ],
            '@import followed by matching @media rule' => [
                'before' => '',
                '@import' => '@import "foo.css";',
                'after' => "\n" . '@media (max-width: 640px) { p { color: green; } }',
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $cssBefore
     * @param string $cssImports
     * @param string $cssAfter
     *
     * @dataProvider provideValidImportRules
     */
    public function emogrifyPreservesValidImportRules($cssBefore, $cssImports, $cssAfter)
    {
        $this->subject->setHtml('<html><p>foo</p></html>');
        $this->subject->setCss($cssBefore . $cssImports . $cssAfter);

        $result = $this->subject->emogrify();

        self::assertContains($cssImports, $result);
    }

    /**
     * @return string[][]
     */
    public function provideInvalidImportRules()
    {
        return [
            '@import after other rule' => [
                'p { color: red; }' . "\n"
                . '@import "foo.css";',
            ],
            '@import after @media rule' => [
                '@media (max-width: 640px) { p { color: red; } }' . "\n"
                . '@import "foo.css";',
            ],
            '@import after incorrectly-cased @charset rule' => [
                '@CHARSET "UTF-8";' . "\n"
                . '@import "foo.css";',
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider provideInvalidImportRules
     */
    public function emogrifyRemovesInvalidImportRules($css)
    {
        $this->subject->setHtml('<html><p>foo</p></html>');
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertNotContains('@import', $result);
    }

    /**
     * @test
     */
    public function emogrifyNotCopiesInlinableRuleAfterImportRuleToStyleElement()
    {
        $cssImport = '@import "foo.css";';
        $cssAfter = 'p { color: red; }';
        $this->subject->setHtml('<html><p>foo</p></html>');
        $this->subject->setCss($cssImport . "\n" . $cssAfter);

        $result = $this->subject->emogrify();

        self::assertNotContainsCss($cssAfter, $result);
    }

    /**
     * @test
     */
    public function copyUninlinableCssToStyleNodeHasNoSideEffects()
    {
        $this->subject->setHtml('<html><a>foo</a><p>bar</p></html>');
        // CSS: `a:hover { color: green; } p:hover { color: blue; }`
        $uninlinableCssRules = [
            [
                'media' => '',
                'selector' => 'a:hover',
                'hasUnmatchablePseudo' => true,
                'declarationsBlock' => 'color: green;',
                'line' => 0,
            ],
            [
                'media' => '',
                'selector' => 'p:hover',
                'hasUnmatchablePseudo' => true,
                'declarationsBlock' => 'color: blue;',
                'line' => 1,
            ],
        ];

        $copyUninlinableCssToStyleNode = new \ReflectionMethod(Emogrifier::class, 'copyUninlinableCssToStyleNode');
        $copyUninlinableCssToStyleNode->setAccessible(true);

        $domDocument = $this->subject->getDomDocument();

        $copyUninlinableCssToStyleNode->invoke($this->subject, $uninlinableCssRules, '');
        $expectedHtml = $domDocument->saveHTML();

        $styleElement = $domDocument->getElementsByTagName('style')->item(0);
        $styleElement->parentNode->removeChild($styleElement);

        $copyUninlinableCssToStyleNode->invoke($this->subject, $uninlinableCssRules, '');

        self::assertSame($expectedHtml, $domDocument->saveHTML());
    }
}
