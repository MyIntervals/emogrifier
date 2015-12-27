<?php
namespace Pelago\Tests\Unit;

use Pelago\Emogrifier;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class EmogrifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    const LF = "\n";

    /**
     * @var string
     */
    private $html4TransitionalDocumentType = '';

    /**
     * @var string
     */
    private $xhtml1StrictDocumentType = '';

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
        $this->html4TransitionalDocumentType = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" ' .
            '"http://www.w3.org/TR/REC-html40/loose.dtd">';
        $this->xhtml1StrictDocumentType = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" ' .
            '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';

        $this->subject = new Emogrifier();
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function emogrifyForNoDataSetReturnsThrowsException()
    {
        $this->subject->emogrify();
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function emogrifyForEmptyHtmlAndEmptyCssThrowsException()
    {
        $this->subject->setHtml('');
        $this->subject->setCss('');

        $this->subject->emogrify();
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function emogrifyBodyContentForNoDataSetReturnsThrowsException()
    {
        $this->subject->emogrifyBodyContent();
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function emogrifyBodyContentForEmptyHtmlAndEmptyCssThrowsException()
    {
        $this->subject->setHtml('');
        $this->subject->setCss('');

        $this->subject->emogrifyBodyContent();
    }

    /**
     * @test
     */
    public function emogrifyAddsHtmlTagIfNoHtmlTagAndNoHeadTagAreProvided()
    {
        $this->subject->setHtml('<p>Hello</p>');

        $result = $this->subject->emogrify();

        self::assertContains('<html>', $result);
    }

    /**
     * @test
     */
    public function emogrifyAddsHtmlTagIfHeadTagIsProvidedButNoHtmlTaqg()
    {
        $this->subject->setHtml('<head><title>Hello</title></head><p>World</p>');

        $result = $this->subject->emogrify();

        self::assertContains('<html>', $result);
    }

    /**
     * @test
     */
    public function emogrifyAddsHeadTagIfNoHtmlTagAndNoHeadTagAreProvided()
    {
        $this->subject->setHtml('<p>Hello</p>');

        $result = $this->subject->emogrify();

        self::assertContains('<head>', $result);
    }

    /**
     * @test
     */
    public function emogrifyAddsHtmlTagIfHtmlTagIsProvidedButNoHeadTaqg()
    {
        $this->subject->setHtml('<html></head><p>World</p></html>');

        $result = $this->subject->emogrify();

        self::assertContains('<head>', $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsDollarSignsAndSquareBrackets()
    {
        $templateMarker = '$[USER:NAME]$';
        $html = $this->html5DocumentType . '<html><p>' . $templateMarker . '</p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($templateMarker, $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsUtf8UmlautsInHtml5()
    {
        $umlautString = 'Küss die Hand, schöne Frau.';
        $html = $this->html5DocumentType . '<html><p>' . $umlautString . '</p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($umlautString, $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsUtf8UmlautsInXhtml()
    {
        $umlautString = 'Öösel läks õunu täis ämber uhkelt ümber.';
        $html = $this->xhtml1StrictDocumentType . '<html<p>' . $umlautString . '</p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($umlautString, $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsUtf8UmlautsInHtml4()
    {
        $umlautString = 'Öösel läks õunu täis ämber uhkelt ümber.';
        $html = $this->html4TransitionalDocumentType . '<html><p>' . $umlautString . '</p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($umlautString, $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsHtmlEntities()
    {
        $entityString = 'a &amp; b &gt; c';
        $html = $this->html5DocumentType . '<html><p>' . $entityString . '</p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($entityString, $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsHtmlEntitiesInXhtml()
    {
        $entityString = 'a &amp; b &gt; c';
        $html = $this->xhtml1StrictDocumentType . '<html<p>' . $entityString . '</p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($entityString, $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsHtmlEntitiesInHtml4()
    {
        $entityString = 'a &amp; b &gt; c';
        $html = $this->html4TransitionalDocumentType . '<html><p>' . $entityString . '</p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($entityString, $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsUtf8UmlautsWithoutDocumentType()
    {
        $umlautString = 'Küss die Hand, schöne Frau.';
        $html = '<html><head></head><p>' . $umlautString . '</p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($umlautString, $result);
    }

   /**
    * @test
    */
    public function emogrifyKeepsUtf8UmlautsWithoutDocumentTypeAndWithoutHtmlAndWithoutHead()
    {
        $umlautString = 'Küss die Hand, schöne Frau.';
        $html = '<p>' . $umlautString . '</p>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($umlautString, $result);
    }

   /**
    * @test
    */
    public function emogrifyKeepsUtf8UmlautsWithoutDocumentTypeAndWithHtmlAndWithoutHead()
    {
        $umlautString = 'Küss die Hand, schöne Frau.';
        $html = '<html><p>' . $umlautString . '</p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($umlautString, $result);
    }

   /**
    * @test
    */
    public function emogrifyKeepsUtf8UmlautsWithoutDocumentTypeAndWithoutHtmlAndWithHead()
    {
        $umlautString = 'Küss die Hand, schöne Frau.';
        $html = '<head></head><p>' . $umlautString . '</p>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($umlautString, $result);
    }

    /**
     * @test
     */
    public function emogrifyForHtmlTagOnlyAndEmptyCssByDefaultAddsHtml5DocumentType()
    {
        $html = '<html></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss('');

        $result = $this->subject->emogrify();

        self::assertContains($this->html5DocumentType, $result);
    }

    /**
     * @test
     */
    public function emogrifyForHtmlTagWithXhtml1StrictDocumentTypeKeepsDocumentType()
    {
        $html = $this->xhtml1StrictDocumentType . '<html></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($this->xhtml1StrictDocumentType, $result);
    }

    /**
     * @test
     */
    public function emogrifyForHtmlTagWithXhtml5DocumentTypeKeepsDocumentType()
    {
        $html = $this->html5DocumentType . '<html></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($this->html5DocumentType, $result);
    }

    /**
     * @test
     */
    public function emogrifyAddsContentTypeMetaTag()
    {
        $html = $this->html5DocumentType . '<p>Hello</p>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $result);
    }

    /**
     * @test
     */
    public function emogrifyForExistingContentTypeMetaTagNotAddsSecondContentTypeMetaTag()
    {
        $html = $this->html5DocumentType .
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>' .
            '<body><p>Hello</p></body></html>';
        $this->subject->setHtml($html);

        $numberOfContentTypeMetaTags = substr_count($this->subject->emogrify(), 'Content-Type');

        self::assertSame(1, $numberOfContentTypeMetaTags);
    }

    /**
     * @test
     */
    public function emogrifyByDefaultRemovesWbrTag()
    {
        $html = $this->html5DocumentType . '<html>foo<wbr/>bar</html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains('foobar', $result);
    }

    /**
     * @test
     */
    public function addUnprocessableTagCausesGivenEmptyTagToBeRemoved()
    {
        $html = $this->html5DocumentType . '<html><p></p></html>';
        $this->subject->setHtml($html);

        $this->subject->addUnprocessableHtmlTag('p');
        $result = $this->subject->emogrify();

        self::assertNotContains('<p>', $result);
    }

    /**
     * @test
     */
    public function addUnprocessableTagNotRemovesGivenTagWithContent()
    {

        $html = $this->html5DocumentType . '<html><p>foobar</p></html>';
        $this->subject->setHtml($html);

        $this->subject->addUnprocessableHtmlTag('p');
        $result = $this->subject->emogrify();

        self::assertContains('<p>', $result);
    }

    /**
     * @test
     */
    public function removeUnprocessableHtmlTagCausesTagToStayAgain()
    {
        $html = $this->html5DocumentType . '<html><p>foo<br/><span>bar</span></p></html>';
        $this->subject->setHtml($html);

        $this->subject->addUnprocessableHtmlTag('p');
        $this->subject->removeUnprocessableHtmlTag('p');
        $result = $this->subject->emogrify();

        self::assertContains('<p>', $result);
    }

    /**
     * @test
     */
    public function emogrifyCanAddMatchingElementRuleOnHtmlElementFromCss()
    {
        $html = $this->html5DocumentType . '<html></html>';
        $this->subject->setHtml($html);
        $styleRule = 'color: #000;';
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
    public function emogrifyNotAddsNotMatchingElementRuleOnHtmlElementFromCss()
    {
        $html = $this->html5DocumentType . '<html></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss('p {color:#000;}');

        $result = $this->subject->emogrify();

        self::assertContains('<html>', $result);
    }

    /**
     * @test
     */
    public function emogrifyCanMatchTwoElements()
    {
        $html = $this->html5DocumentType . '<html><p></p><p></p></html>';
        $this->subject->setHtml($html);
        $styleRule = 'color: #000;';
        $this->subject->setCss('p {' . $styleRule . '}');

        $result = $this->subject->emogrify();

        self::assertSame(
            2,
            substr_count($result, '<p style="' . $styleRule . '">')
        );
    }

    /**
     * @test
     */
    public function emogrifyCanAssignTwoStyleRulesFromSameMatcherToElement()
    {
        $html = $this->html5DocumentType . '<html><p></p></html>';
        $this->subject->setHtml($html);
        $styleRulesIn = 'color:#000; text-align:left;';
        $this->subject->setCss('p {' . $styleRulesIn . '}');

        $result = $this->subject->emogrify();

        $styleRulesOut = 'color: #000; text-align: left;';
        self::assertContains(
            '<p style="' . $styleRulesOut . '">',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyCanMatchAttributeOnlySelector()
    {
        $html = $this->html5DocumentType . '<html><p hidden="hidden"></p></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss('[hidden] { color:red; }');

        $result = $this->subject->emogrify();

        self::assertContains('<p hidden="hidden" style="color: red;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyCanAssignStyleRulesFromTwoIdenticalMatchersToElement()
    {
        $html = $this->html5DocumentType . '<html><p></p></html>';
        $this->subject->setHtml($html);
        $styleRule1 = 'color: #000;';
        $styleRule2 = 'text-align: left;';
        $this->subject->setCss('p {' . $styleRule1 . '}  p {' . $styleRule2 . '}');

        $result = $this->subject->emogrify();

        self::assertContains(
            '<p style="' . $styleRule1 . ' ' . $styleRule2 . '">',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyCanAssignStyleRulesFromTwoDifferentMatchersToElement()
    {
        $html = $this->html5DocumentType . '<html><p class="x"></p></html>';
        $this->subject->setHtml($html);
        $styleRule1 = 'color: #000;';
        $styleRule2 = 'text-align: left;';
        $this->subject->setCss('p {' . $styleRule1 . '} .x {' . $styleRule2 . '}');

        $result = $this->subject->emogrify();

        self::assertContains(
            '<p class="x" style="' . $styleRule1 . ' ' . $styleRule2 . '">',
            $result
        );
    }

    /**
     * Data provide for selectors.
     *
     * @return string[][]
     */
    public function selectorDataProvider()
    {
        $styleRule = 'color: red;';
        $styleAttribute = 'style="' . $styleRule . '"';

        return [
            'universal selector HTML'
                => ['* {' . $styleRule . '} ', '#<html id="html" ' . $styleAttribute . '>#'],
            'universal selector BODY'
                => ['* {' . $styleRule . '} ', '#<body ' . $styleAttribute . '>#'],
            'universal selector P'
                => ['* {' . $styleRule . '} ', '#<p[^>]*' . $styleAttribute . '>#'],
            'type selector matches first P'
                => ['p {' . $styleRule . '} ', '#<p class="p-1" ' . $styleAttribute . '>#'],
            'type selector matches second P'
                => ['p {' . $styleRule . '} ', '#<p class="p-2" ' . $styleAttribute . '>#'],
            'descendant selector P SPAN'
                => ['p span {' . $styleRule . '} ', '#<span ' . $styleAttribute . '>#'],
            'descendant selector BODY SPAN'
                => ['body span {' . $styleRule . '} ', '#<span ' . $styleAttribute . '>#'],
            'child selector P > SPAN matches direct child'
                => ['p > span {' . $styleRule . '} ', '#<span ' . $styleAttribute . '>#'],
            'child selector BODY > SPAN not matches grandchild'
                => ['body > span {' . $styleRule . '} ', '#<span>#'],
            'adjacent selector P + P not matches first P' => ['p + p {' . $styleRule . '} ', '#<p class="p-1">#'],
            'adjacent selector P + P matches second P'
                => ['p + p {' . $styleRule . '} ', '#<p class="p-2" style="' . $styleRule . '">#'],
            'adjacent selector P + P matches third P'
                => ['p + p {' . $styleRule . '} ', '#<p class="p-3" style="' . $styleRule . '">#'],
            'ID selector #HTML' => ['#html {' . $styleRule . '} ', '#<html id="html" ' . $styleAttribute . '>#'],
            'type and ID selector HTML#HTML'
                => ['html#html {' . $styleRule . '} ', '#<html id="html" ' . $styleAttribute . '>#'],
            'class selector .P-1' => ['.p-1 {' . $styleRule . '} ', '#<p class="p-1" ' . $styleAttribute . '>#'],
            'type and class selector P.P-1'
                => ['p.p-1 {' . $styleRule . '} ', '#<p class="p-1" ' . $styleAttribute . '>#'],
            'attribute presence selector SPAN[title] matches element with matching attribute'
                => ['span[title] {' . $styleRule . '} ', '#<span title="bonjour" ' . $styleAttribute . '>#'],
            'attribute presence selector SPAN[title] not matches element without any attributes'
                => ['span[title] {' . $styleRule . '} ', '#<span>#'],
            'attribute value selector [id="html"] matches element with matching attribute value' => [
                '[id="html"] {' . $styleRule . '} ', '#<html id="html" ' . $styleAttribute . '>#'
            ],
            'attribute value selector SPAN[title] matches element with matching attribute value' => [
                'span[title="bonjour"] {' . $styleRule . '} ', '#<span title="bonjour" ' . $styleAttribute . '>#'
            ],
            'attribute value selector SPAN[title] not matches element with other attribute value'
                => ['span[title="bonjour"] {' . $styleRule . '} ', '#<span title="buenas dias">#'],
            'attribute value selector SPAN[title] not matches element without any attributes'
                => ['span[title="bonjour"] {' . $styleRule . '} ', '#<span>#'],
            'BODY:first-child matches first child'
                => ['body:first-child {' . $styleRule . '} ', '#<p class="p-1" style="' . $styleRule . '">#'],
            'BODY:first-child not matches middle child'
                => ['body:first-child {' . $styleRule . '} ', '#<p class="p-2">#'],
            'BODY:first-child not matches last child'
                => ['body:first-child {' . $styleRule . '} ', '#<p class="p-3">#'],
            'BODY:last-child not matches first child' => ['body:last-child {' . $styleRule . '} ', '#<p class="p-1">#'],
            'BODY:last-child not matches middle child'
                => ['body:last-child {' . $styleRule . '} ', '#<p class="p-2">#'],
            'BODY:last-child matches last child'
                => ['body:last-child {' . $styleRule . '} ', '#<p class="p-3" style="' . $styleRule . '">#'],
        ];
    }

    /**
     * @test
     *
     * @param string $css the complete CSS
     * @param string $htmlRegularExpression regular expression for the the HTML that needs to be contained in the HTML
     *
     * @dataProvider selectorDataProvider
     */
    public function emogrifierMatchesSelectors($css, $htmlRegularExpression)
    {
        $html = $this->html5DocumentType .
            '<html id="html">' .
            '  <body>' .
            '    <p class="p-1"><span>some text</span></p>' .
            '    <p class="p-2"><span title="bonjour">some</span> text</p>' .
            '    <p class="p-3"><span title="buenas dias">some</span> more text</p>' .
            '  </body>' .
            '</html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertRegExp($htmlRegularExpression, $result);
    }

    /**
     * Data provider for emogrifyDropsWhitespaceFromCssDeclarations.
     *
     * @return string[][]
     */
    public function cssDeclarationWhitespaceDroppingDataProvider()
    {
        return [
            'no whitespace, trailing semicolon' => ['color:#000;', 'color: #000;'],
            'no whitespace, no trailing semicolon' => ['color:#000', 'color: #000;'],
            'space after colon, no trailing semicolon' => ['color: #000', 'color: #000;'],
            'space before colon, no trailing semicolon' => ['color :#000', 'color: #000;'],
            'space before property name, no trailing semicolon' => [' color:#000', 'color: #000;'],
            'space before trailing semicolon' => [' color:#000 ;', 'color: #000;'],
            'space after trailing semicolon' => [' color:#000; ', 'color: #000;'],
            'space after property value, no trailing semicolon' => [' color:#000 ', 'color: #000;'],
            'space after property value, trailing semicolon' => [' color:#000; ', 'color: #000;'],
            'newline before property name, trailing semicolon' => ["\ncolor:#222;", 'color: #222;'],
            'newline after property semicolon' => ["color:#222;\n", 'color: #222;'],
            'newline before colon, trailing semicolon' => ["color\n:#333;", 'color: #333;'],
            'newline after colon, trailing semicolon' => ["color:\n#333;", 'color: #333;'],
            'newline after semicolon' => ["color:#333\n;", 'color: #333;'],
        ];
    }

    /**
     * @test
     *
     * @param string $cssDeclaration the CSS declaration block (without the curly braces)
     * @param string $expectedStyleAttributeContent the expected value of the style attribute
     *
     * @dataProvider cssDeclarationWhitespaceDroppingDataProvider
     */
    public function emogrifyDropsLeadingAndTrailingWhitespaceFromCssDeclarations(
        $cssDeclaration,
        $expectedStyleAttributeContent
    ) {
        $html = $this->html5DocumentType . '<html></html>';
        $css = 'html {' . $cssDeclaration . '}';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains(
            'html style="' . $expectedStyleAttributeContent . '">',
            $result
        );
    }

    /**
     * Data provider for emogrifyFormatsCssDeclarations.
     *
     * @return string[][]
     */
    public function formattedCssDeclarationDataProvider()
    {
        return [
            'one declaration' => ['color: #000;', 'color: #000;'],
            'one declaration with dash in property name' => ['font-weight: bold;', 'font-weight: bold;'],
            'one declaration with space in property value' => ['margin: 0 4px;', 'margin: 0 4px;'],
            'two declarations separated by semicolon' => ['color: #000;width: 3px;', 'color: #000; width: 3px;'],
            'two declarations separated by semicolon and space'
                => ['color: #000; width: 3px;', 'color: #000; width: 3px;'],
            'two declarations separated by semicolon and linefeed' => [
                'color: #000;' . self::LF . 'width: 3px;', 'color: #000; width: 3px;'
            ],
            'two declarations separated by semicolon and Windows line ending' => [
                "color: #000;\r\nwidth: 3px;", 'color: #000; width: 3px;'
            ],
            'one declaration with leading dash in property name' => [
                '-webkit-text-size-adjust:none;', '-webkit-text-size-adjust: none;'
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
        $html = $this->html5DocumentType . '<html></html>';
        $css = 'html {' . $cssDeclarationBlock . '}';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains(
            'html style="' . $expectedStyleAttributeContent . '">',
            $result
        );
    }

    /**
     * Data provider for emogrifyInvalidDeclaration.
     *
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
    public function emogrifyDropsInvalidDeclaration($cssDeclarationBlock)
    {
        $html = $this->html5DocumentType . '<html></html>';
        $css = 'html {' . $cssDeclarationBlock . '}';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains('<html style="">', $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsExistingStyleAttributes()
    {
        $styleAttribute = 'style="color: #ccc;"';
        $html = $this->html5DocumentType . '<html ' . $styleAttribute . '></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($styleAttribute, $result);
    }

    /**
     * @test
     */
    public function emogrifyAddsCssAfterExistingStyle()
    {
        $styleAttributeValue = 'color: #ccc;';
        $html = $this->html5DocumentType . '<html style="' . $styleAttributeValue . '"></html>';
        $this->subject->setHtml($html);
        $cssDeclarations = 'margin: 0 2px;';
        $css = 'html {' . $cssDeclarations . '}';
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains(
            'style="' . $styleAttributeValue . ' ' . $cssDeclarations . '"',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyCanMatchMinifiedCss()
    {
        $html = $this->html5DocumentType . '<html><p></p></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss('p{color:blue;}html{color:red;}');

        $result = $this->subject->emogrify();

        self::assertContains('<html style="color: red;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyLowercasesAttributeNamesFromStyleAttributes()
    {
        $html = $this->html5DocumentType . '<html style="COLOR:#ccc;"></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains('style="color: #ccc;"', $result);
    }

    /**
     * @test
     */
    public function emogrifyLowerCasesAttributeNames()
    {
        $html = $this->html5DocumentType . '<html></html>';
        $this->subject->setHtml($html);
        $cssIn = 'html {mArGiN:0 2pX;}';
        $this->subject->setCss($cssIn);

        $result = $this->subject->emogrify();

        self::assertContains('style="margin: 0 2pX;"', $result);
    }

    /**
     * @test
     */
    public function emogrifyPreservesCaseForAttributeValuesFromPassedInCss()
    {
        $css = 'content: \'Hello World\';';
        $html = $this->html5DocumentType . '<html><body><p>target</p></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss('p {' . $css . '}');

        $result = $this->subject->emogrify();

        self::assertContains(
            '<p style="' . $css . '">target</p>',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyPreservesCaseForAttributeValuesFromParsedStyleBlock()
    {
        $css = 'content: \'Hello World\';';
        $html = $this->html5DocumentType . '<html><head><style>p {' .
            $css . '}</style></head><body><p>target</p></body></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains(
            '<p style="' . $css . '">target</p>',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyRemovesStyleNodes()
    {
        $html = $this->html5DocumentType . '<html><style type="text/css"></style></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertNotContains('<style', $result);
    }

    /**
     * @test
     */
    public function emogrifyIgnoresInvalidCssSelector()
    {
        $html = $this->html5DocumentType .
            '<html><style type="text/css">p{color:red;} <style data-x="1">html{cursor:text;}</style></html>';
        $this->subject->setHtml($html);

        $hasError = false;
        set_error_handler(function ($errorNumber, $errorMessage) use (&$hasError) {
            if ($errorMessage === 'DOMXPath::query(): Invalid expression') {
                return true;
            }

            $hasError = true;
            return true;
        });

        $this->subject->emogrify();
        restore_error_handler();

        self::assertFalse($hasError);
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
            '@import directive' => ['@import "foo.css";', '@import'],
            'style in "aural" media type rule' => ['@media aural {p {color: #000;}}', '#000'],
            'style in "braille" media type rule' => ['@media braille {p {color: #000;}}', '#000'],
            'style in "embossed" media type rule' => ['@media embossed {p {color: #000;}}', '#000'],
            'style in "handheld" media type rule' => ['@media handheld {p {color: #000;}}', '#000'],
            'style in "projection" media type rule' => ['@media projection {p {color: #000;}}', '#000'],
            'style in "speech" media type rule' => ['@media speech {p {color: #000;}}', '#000'],
            'style in "tty" media type rule' => ['@media tty {p {color: #000;}}', '#000'],
            'style in "tv" media type rule' => ['@media tv {p {color: #000;}}', '#000'],
        ];
    }

    /**
     * @test
     *
     * @param string $css
     * @param string $markerNotExpectedInHtml
     *
     * @dataProvider unneededCssThingsDataProvider
     */
    public function emogrifyFiltersUnneededCssThings($css, $markerNotExpectedInHtml)
    {
        $html = $this->html5DocumentType . '<html><p>foo</p></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertNotContains($markerNotExpectedInHtml, $result);
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
            'style in media type rule' => ['@media {p {color: #000;}}'],
            'style in "screen" media type rule' => ['@media screen {p {color: #000;}}'],
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
        $html = $this->html5DocumentType . '<html><p>foo</p></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains($css, $result);
    }

    /**
     * @test
     */
    public function removeAllowedMediaTypeRemovesStylesForTheGivenMediaType()
    {
        $css = '@media screen { html {} }';
        $html = $this->html5DocumentType . '<html></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);
        $this->subject->removeAllowedMediaType('screen');

        $result = $this->subject->emogrify();

        self::assertNotContains($css, $result);
    }

    /**
     * @test
     */
    public function addAllowedMediaTypeKeepsStylesForTheGivenMediaType()
    {
        $css = '@media braille { html { some-property: value; } }';
        $html = $this->html5DocumentType . '<html></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);
        $this->subject->addAllowedMediaType('braille');

        $result = $this->subject->emogrify();

        self::assertContains($css, $result);
    }

    /**
     * @test
     */
    public function emogrifyAddsMissingHeadElement()
    {
        $html = $this->html5DocumentType . '<html></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss('@media all { html {} }');

        $result = $this->subject->emogrify();

        self::assertContains('<head>', $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepExistingHeadElementContent()
    {
        $html = $this->html5DocumentType . '<html><head><!-- original content --></head></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss('@media all { html {} }');

        $result = $this->subject->emogrify();

        self::assertContains('<!-- original content -->', $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepExistingHeadElementAddStyleElement()
    {
        $html = $this->html5DocumentType . '<html><head><!-- original content --></head></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss('@media all { html {} }');

        $result = $this->subject->emogrify();

        self::assertContains('<style type="text/css">', $result);
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
            'style in media type rule without specification' => ['@media { h1 { color:red; } }'],
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
        $html = $this->html5DocumentType . PHP_EOL . '<html><h1></h1><p></p></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains($css, $result);
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
        $html = $this->html5DocumentType . PHP_EOL . '<html><style type="text/css">' . $css .
            '</style><h1></h1><p></p></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains($css, $result);
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
        $html = $this->html5DocumentType . PHP_EOL . '<html><h1></h1></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertNotContains('style="color:red"', $result);
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
    public function emogrifyWithInvalidMediaQueryaNotContainsInnerCss($css)
    {
        $html = $this->html5DocumentType . PHP_EOL . '<html><h1></h1></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertNotContains($css, $result);
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider invalidMediaPreserveDataProvider
     */
    public function emogrifyWithInValidMediaQueryNotContainsInlineCss($css)
    {
        $html = $this->html5DocumentType . PHP_EOL . '<html><h1></h1></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertNotContains('style="color: red"', $result);
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider invalidMediaPreserveDataProvider
     */
    public function emogrifyFromHtmlWithInValidMediaQueryNotContainsInnerCss($css)
    {
        $html = $this->html5DocumentType . PHP_EOL . '<html><style type="text/css">' . $css .
            '</style><h1></h1></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertNotContains($css, $result);
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider invalidMediaPreserveDataProvider
     */
    public function emogrifyFromHtmlWithInValidMediaQueryNotContainsInlineCss($css)
    {
        $html = $this->html5DocumentType . PHP_EOL . '<html><style type="text/css">' . $css .
            '</style><h1></h1></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertNotContains('style="color: red"', $result);
    }

    /**
     * @test
     */
    public function emogrifyAppliesCssFromStyleNodes()
    {
        $styleAttributeValue = 'color: #ccc;';
        $html = $this->html5DocumentType .
            '<html><style type="text/css">html {' . $styleAttributeValue . '}</style></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains(
            '<html style="' . $styleAttributeValue . '">',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyWhenDisabledNotAppliesCssFromStyleBlocks()
    {
        $styleAttributeValue = 'color: #ccc;';
        $html = $this->html5DocumentType .
            '<html><style type="text/css">html {' . $styleAttributeValue . '}</style></html>';
        $this->subject->setHtml($html);
        $this->subject->disableStyleBlocksParsing();

        $result = $this->subject->emogrify();

        self::assertNotContains(
            '<html style="' . $styleAttributeValue . '">',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyWhenStyleBlocksParsingDisabledKeepInlineStyles()
    {
        $styleAttributeValue = 'text-align: center;';
        $html = $this->html5DocumentType . '<html><head><style type="text/css">p { color: #ccc; }</style></head>' .
            '<body><p style="' . $styleAttributeValue . '">paragraph</p></body></html>';
        $this->subject->setHtml($html);
        $this->subject->disableStyleBlocksParsing();

        $result = $this->subject->emogrify();

        self::assertContains(
            '<p style="' . $styleAttributeValue . '">',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyWhenDisabledNotAppliesCssFromInlineStyles()
    {
        $styleAttributeValue = 'color: #ccc;';
        $html = $this->html5DocumentType . '<html style="' . $styleAttributeValue . '"></html>';
        $this->subject->setHtml($html);
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
        $html = $this->html5DocumentType .
            '<html><head><style type="text/css">p { ' . $styleAttributeValue . ' }</style></head>' .
            '<body><p style="text-align: center;">paragraph</p></body></html>';
        $this->subject->setHtml($html);
        $this->subject->disableInlineStyleAttributesParsing();

        $result = $this->subject->emogrify();

        self::assertContains(
            '<p style="' . $styleAttributeValue . '">',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyAppliesCssWithUpperCaseSelector()
    {
        $html = $this->html5DocumentType .
            '<html><style type="text/css">P { color:#ccc; }</style><body><p>paragraph</p></body></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains('<p style="color: #ccc;">', $result);
    }

    /**
     * Emogrify was handling case differently for passed in CSS vs CSS parsed from style blocks.
     * @test
     */
    public function emogrifyAppliesCssWithMixedCaseAttributesInStyleBlock()
    {
        $html = $this->html5DocumentType .
            '<html><head><style>#topWrap p {padding-bottom: 1px;PADDING-TOP: 0;}</style></head>' .
            '<body><div id="topWrap"><p style="text-align: center;">some content</p></div></body></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains('<p style="text-align: center; padding-bottom: 1px; padding-top: 0;">', $result);
    }

    /**
     * Passed in CSS sets the order, but style block CSS overrides values.
     * @test
     */
    public function emogrifyMergesCssWithMixedCaseAttribute()
    {
        $css = 'p { margin: 0; padding-TOP: 0; PADDING-bottom: 1PX;}';
        $html = $this->html5DocumentType .
            '<html><head><style>#topWrap p {padding-bottom: 3px;PADDING-TOP: 1px;}</style></head>' .
            '<body><div id="topWrap"><p style="text-align: center;">some content</p></div></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains(
            '<p style="text-align: center; margin: 0; padding-top: 1px; padding-bottom: 3px;">',
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyMergesCssWithMixedUnits()
    {
        $css = 'p { margin: 1px; padding-bottom:0;}';
        $html = $this->html5DocumentType .
            '<html><head><style>#topWrap p {margin:0;padding-bottom: 1px;}</style></head>' .
            '<body><div id="topWrap"><p style="text-align: center;">some content</p></div></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains('<p style="text-align: center; margin: 0; padding-bottom: 1px;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyByDefaultRemovesElementsWithDisplayNoneFromExternalCss()
    {
        $css = 'div.foo { display: none; }';
        $html = $this->html5DocumentType . '<html><body><div class="bar"></div><div class="foo"></div></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains('<div class="bar"></div>', $result);
    }

    /**
     * @test
     */
    public function emogrifyByDefaultRemovesElementsWithDisplayNoneInStyleAttribute()
    {
        $html = $this->html5DocumentType .
            '<html><body><div class="bar"></div><div class="foobar" style="display: none;"></div>' .
            '</body></html>';
        $this->subject->setHtml($html);

        $result = $this->subject->emogrify();

        self::assertContains('<div class="bar"></div>', $result);
    }

    /**
     * @test
     */
    public function emogrifyAfterDisableInvisibleNodeRemovalPreservesInvisibleElements()
    {
        $css = 'div.foo { display: none; }';
        $html = $this->html5DocumentType . '<html><body><div class="bar"></div><div class="foo"></div></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);
        $this->subject->disableInvisibleNodeRemoval();

        $result = $this->subject->emogrify();

        self::assertContains('<div class="foo" style="display: none;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyKeepsCssMediaQueriesWithCssCommentAfterMediaQuery()
    {
        $css = '@media only screen and (max-width: 480px) { body { color: #ffffff } /* some comment */ }';
        $html = $this->html5DocumentType . '<html><body></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains('@media only screen and (max-width: 480px)', $result);
    }

    /**
     * @test
     */
    public function emogrifyForXhtmlDocumentTypeConvertsXmlSelfClosingTagsToNonXmlSelfClosingTag()
    {
        $this->subject->setHtml($this->xhtml1StrictDocumentType . '<html><body><br/></body></html>');

        $result = $this->subject->emogrify();

        self::assertContains('<body><br></body>', $result);
    }

    /**
     * @test
     */
    public function emogrifyForHtml5DocumentTypeKeepsNonXmlSelfClosingTagsAsNonXmlSelfClosing()
    {
        $this->subject->setHtml($this->html5DocumentType . '<html><body><br></body></html>');

        $result = $this->subject->emogrify();

        self::assertContains('<body><br></body>', $result);
    }

    /**
     * @test
     */
    public function emogrifyForHtml5DocumentTypeConvertXmlSelfClosingTagsToNonXmlSelfClosingTag()
    {
        $this->subject->setHtml($this->html5DocumentType . '<html><body><br/></body></html>');

        $result = $this->subject->emogrify();

        self::assertContains('<body><br></body>', $result);
    }

    /**
     * @test
     */
    public function emogrifyAutomaticallyClosesUnclosedTag()
    {
        $this->subject->setHtml($this->html5DocumentType . '<html><body><p></body></html>');

        $result = $this->subject->emogrify();

        self::assertContains('<body><p></p></body>', $result);
    }

    /**
     * @test
     */
    public function emogrifyReturnsCompleteHtmlDocument()
    {
        $this->subject->setHtml($this->html5DocumentType . '<html><body><p></p></body></html>');

        $result = $this->subject->emogrify();

        self::assertSame(
            $this->html5DocumentType . self::LF .
            '<html>' . self::LF .
            '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>' . self::LF .
            '<body><p></p></body>' . self::LF .
            '</html>' . self::LF,
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyBodyContentReturnsBodyContentFromHtml()
    {
        $this->subject->setHtml($this->html5DocumentType . '<html><body><p></p></body></html>');

        $result = $this->subject->emogrifyBodyContent();

        self::assertSame(
            '<p></p>' . self::LF,
            $result
        );
    }

    /**
     * @test
     */
    public function emogrifyBodyContentReturnsBodyContentFromContent()
    {
        $this->subject->setHtml('<p></p>');

        $result = $this->subject->emogrifyBodyContent();

        self::assertSame(
            '<p></p>' . self::LF,
            $result
        );
    }

    /**
     * @test
     */
    public function importantInExternalCssOverwritesInlineCss()
    {
        $css = 'p { margin: 1px !important; }';
        $html = $this->html5DocumentType .
            '<html><head</head><body><p style="margin: 2px;">some content</p></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 1px !important;">', $result);
    }

    /**
     * @test
     */
    public function importantInExternalCssKeepsInlineCssForOtherAttributes()
    {
        $css = 'p { margin: 1px !important; }';
        $html = $this->html5DocumentType .
            '<html><head</head><body><p style="margin: 2px; text-align: center;">some content</p></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 1px !important; text-align: center;">', $result);
    }

    /**
     * @test
     */
    public function emogrifyHandlesImportantStyleTagCaseInsensitive()
    {
        $css = 'p { margin: 1px !ImPorTant; }';
        $html = $this->html5DocumentType .
            '<html><head</head><body><p style="margin: 2px;">some content</p></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 1px !ImPorTant;">', $result);
    }

    /**
     * @test
     */
    public function secondImportantStyleOverwritesFirstOne()
    {
        $css = 'p { margin: 1px !important; } p { margin: 2px !important; }';
        $html = $this->html5DocumentType .
            '<html><head</head><body><p>some content</p></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains(
            '<p style="margin: 2px !important;">',
            $result
        );
    }

    /**
     * @test
     */
    public function secondNonImportantStyleOverwritesFirstOne()
    {
        $css = 'p { margin: 1px; } p { margin: 2px; }';
        $html = $this->html5DocumentType .
            '<html><head</head><body><p>some content</p></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains(
            '<p style="margin: 2px;">',
            $result
        );
    }

    /**
     * @test
     */
    public function secondNonImportantStyleNotOverwritesFirstImportantOne()
    {
        $css = 'p { margin: 1px !important; } p { margin: 2px; }';
        $html = $this->html5DocumentType .
            '<html><head</head><body><p>some content</p></body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains(
            '<p style="margin: 1px !important;">',
            $result
        );
    }

    /**
     * @test
     */
    public function irrelevantMediaQueriesAreRemoved()
    {
        $uselessQuery = '@media all and (max-width: 500px) { em { color:red; } }';
        $this->subject->setCss($uselessQuery);
        $this->subject->setHtml($this->html5DocumentType . '<html><body><p></p></body></html>');

        $result = $this->subject->emogrify();

        self::assertNotContains($uselessQuery, $result);
    }

    /**
     * @test
     */
    public function relevantMediaQueriesAreRetained()
    {
        $usefulQuery = '@media all and (max-width: 500px) { p { color:red; } }';
        $this->subject->setCss($usefulQuery);
        $this->subject->setHtml($this->html5DocumentType . '<html><body><p></p></body></html>');

        $result = $this->subject->emogrify();

        self::assertContains($usefulQuery, $result);
    }

    /**
     * @test
     */
    public function importantStyleRuleFromInlineCssOverwritesImportantStyleRuleFromExternalCss()
    {
        $css = 'p { margin: 1px !important; padding: 1px;}';
        $html = $this->html5DocumentType .
            '<html><head</head><body><p style="margin: 2px !important; text-align: center;">some content</p>' .
            '</body></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 2px !important; text-align: center; padding: 1px;">', $result);
    }

    /**
     * @test
     */
    public function addExcludedSelectorRemovesMatchingElementsFromEmogrification()
    {
        $css = 'p { margin: 0; }';
        $this->subject->setHtml($this->html5DocumentType . '<html><body><p class="x"></p></body></html>');
        $this->subject->setCss($css);
        $this->subject->addExcludedSelector('p.x');

        $result = $this->subject->emogrify();

        self::assertContains('<p class="x"></p>', $result);
    }

    /**
     * @test
     */
    public function addExcludedSelectorExcludesMatchingElementEventWithWhitespaceAroundSelector()
    {
        $css = 'p { margin: 0; }';
        $this->subject->setHtml($this->html5DocumentType . '<html><body><p class="x"></p></body></html>');
        $this->subject->setCss($css);
        $this->subject->addExcludedSelector(' p.x ');

        $result = $this->subject->emogrify();

        self::assertContains('<p class="x"></p>', $result);
    }

    /**
     * @test
     */
    public function addExcludedSelectorKeepsNonMatchingElementsInEmogrification()
    {
        $css = 'p { margin: 0; }';
        $this->subject->setHtml($this->html5DocumentType . '<html><body><p></p></body></html>');
        $this->subject->setCss($css);
        $this->subject->addExcludedSelector('p.x');

        $result = $this->subject->emogrify();

        self::assertContains('<p style="margin: 0;"></p>', $result);
    }

    /**
     * @test
     */
    public function removeExcludedSelectorGetsMatchingElementsToBeEmogrifiedAgain()
    {
        $css = 'p { margin: 0; }';
        $this->subject->setHtml($this->html5DocumentType . '<html><body><p class="x"></p></body></html>');
        $this->subject->setCss($css);
        $this->subject->addExcludedSelector('p.x');
        $this->subject->removeExcludedSelector('p.x');

        $result = $this->subject->emogrify();

        self::assertContains('<p class="x" style="margin: 0;"></p>', $result);
    }

    /**
     * @test
     */
    public function emptyMediaQueriesAreRemoved()
    {
        $emptyQuery = '@media all and (max-width: 500px) { }';
        $this->subject->setCss($emptyQuery);
        $this->subject->setHtml($this->html5DocumentType . '<html><body><p></p></body></html>');

        $result = $this->subject->emogrify();

        self::assertNotContains($emptyQuery, $result);
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
        $this->subject->setHtml($this->html5DocumentType . '<html><body>' .
            '<p class="medium">medium</p>' .
            '<p class="small">small</p>' .
            '</body></html>');

        $result = $this->subject->emogrify();

        self::assertSame(
            1,
            substr_count($result, '<style type="text/css">' . $css . '</style>')
        );
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
            $this->html5DocumentType . '<html><body>' .
            '<p class="medium">medium</p>' .
            '<p class="small">small</p>' .
            '</body></html>'
        );

        $result = $this->subject->emogrify();

        self::assertSame(
            1,
            substr_count($result, '<style type="text/css">' . $css . '</style>')
        );
    }

    /**
     * @test
     */
    public function multipleMediaQueriesAreAppliedOnlyOnce()
    {
        $css = "@media all {\n" .
            ".medium {font-size:18px;\n" .
            ".small {font-size:14px;}\n" .
            '}' .
            "@media screen {\n" .
            ".medium {font-size:24px;}\n" .
            ".small {font-size:18px;}\n" .
            '}';
        $this->subject->setCss($css);
        $this->subject->setHtml(
            $this->html5DocumentType . '<html><body>' .
            '<p class="medium">medium</p>' .
            '<p class="small">small</p>' .
            '</body></html>'
        );

        $result = $this->subject->emogrify();

        self::assertSame(
            1,
            substr_count($result, '<style type="text/css">' . $css . '</style>')
        );
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
     * @param string $dataUriMediaType
     * @dataProvider dataUriMediaTypeDataProvider
     */
    public function dataUrisAreConserved($dataUriMediaType)
    {
        $html = $this->html5DocumentType . '<html></html>';
        $this->subject->setHtml($html);
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
     * Data provider for CSS to HTML mapping.
     *
     * @return string[][]
     */
    public function matchingCssToHtmlMappingDataProvider()
    {
        return [
            'background-color => bgcolor'
                => ['<p>hi</p>', 'p {background-color: red;}', 'p', 'bgcolor="red"'],
            'background-color (with !important) => bgcolor'
                => ['<p>hi</p>', 'p {background-color: red !important;}', 'p', 'bgcolor="red"'],
            'p.text-align => align'
                => ['<p>hi</p>', 'p {text-align: justify;}', 'p', 'align="'],
            'div.text-align => align'
                => ['<div>hi</div>', 'div {text-align: justify;}', 'div', 'align="'],
            'td.text-align => align'
                => ['<table><tr><td>hi</td></tr></table>', 'td {text-align: justify;}', 'td', 'align="'],
            'text-align: left => align=left'
                => ['<p>hi</p>', 'p {text-align: left;}', 'p', 'align="left"'],
            'text-align: right => align=right'
                => ['<p>hi</p>', 'p {text-align: right;}', 'p', 'align="right"'],
            'text-align: center => align=center'
                => ['<p>hi</p>', 'p {text-align: center;}', 'p', 'align="center"'],
            'text-align: justify => align:justify'
                => ['<p>hi</p>', 'p {text-align: justify;}', 'p', 'align="justify"'],
            'img.float: right => align=right'
                => ['<img>', 'img {float: right;}', 'img', 'align="right"'],
            'img.float: left => align=left'
                => ['<img>', 'img {float: left;}', 'img', 'align="left"'],
            'table.float: right => align=right'
                => ['<table></table>', 'table {float: right;}', 'table', 'align="right"'],
            'table.float: left => align=left'
                => ['<table></table>', 'table {float: left;}', 'table', 'align="left"'],
            'table.border-spacing: 0 => cellspacing=0'
                => ['<table><tr><td></td></tr></table>', 'table {border-spacing: 0;}', 'table', 'cellspacing="0"'],
            'background => bgcolor'
                => ['<p>Bonjour</p>', 'p {background: red top;}', 'p', 'bgcolor="red"'],
            'width with px'
                => ['<p>Hello</p>', 'p {width: 100px;}', 'p', 'width="100"'],
            'width with %'
                => ['<p>Hello</p>', 'p {width: 50%;}', 'p', 'width="50%"'],
            'height with px'
                => ['<p>Hello</p>', 'p {height: 100px;}', 'p', 'height="100"'],
            'height with %'
                => ['<p>Hello</p>', 'p {height: 50%;}', 'p', 'height="50%"'],
            'img.margin: 0 auto (= horizontal centering) => align=center'
                => ['<img>', 'img {margin: 0 auto;}', 'img', 'align="center"'],
            'img.margin: auto (= horizontal centering) => align=center'
                => ['<img>', 'img {margin: auto;}', 'img', 'align="center"'],
            'img.margin: 10 auto 30 auto (= horizontal centering) => align=center'
                => ['<img>', 'img {margin: 10 auto 30 auto;}', 'img', 'align="center"'],
            'table.margin: 0 auto (= horizontal centering) => align=center'
                => ['<table></table>', 'table {margin: 0 auto;}', 'table', 'align="center"'],
            'table.margin: auto (= horizontal centering) => align=center'
                => ['<table></table>', 'table {margin: auto;}', 'table', 'align="center"'],
            'table.margin: 10 auto 30 auto (= horizontal centering) => align=center'
                => ['<table></table>', 'table {margin: 10 auto 30 auto;}', 'table', 'align="center"'],
            'img.border: none => border=0'
                => ['<img>', 'img {border: none;}', 'img', 'border="0"'],
            'img.border: 0 => border=0'
                => ['<img>', 'img {border: none;}', 'img', 'border="0"'],
            'table.border: none => border=0'
                => ['<table></table>', 'table {border: none;}', 'table', 'border="0"'],
            'table.border: 0 => border=0'
                => ['<table></table>', 'table {border: none;}', 'table', 'border="0"'],
        ];
    }

    /**
     * @test
     * @param string $body          The HTML
     * @param string $css           The complete CSS
     * @param string $tagName       The name of the tag that should be modified
     * @param string $attributes    The attributes that are expected on the element
     *
     * @dataProvider matchingCssToHtmlMappingDataProvider
     */
    public function emogrifierMapsSuitableCssToHtmlIfFeatureIsEnabled($body, $css, $tagName, $attributes)
    {
        $this->subject->setHtml($this->html5DocumentType . '<html><body>' . $body . '</body></html>');
        $this->subject->setCss($css);
        $this->subject->enableCssToHtmlMapping();

        $html = $this->subject->emogrify();

        self::assertContains(
            '<' . $tagName . ' ' . $attributes,
            $html
        );
    }

    /**
     * Data provider for CSS to HTML mapping.
     *
     * @return string[][]
     */
    public function notMatchingCssToHtmlMappingDataProvider()
    {
        return [
            'background URL'
                => ['<p>Hello</p>', 'p {background: url(bg.png);}', 'bgcolor'],
            'background URL with position'
                => ['<p>Hello</p>', 'p {background: url(bg.png) top;}', 'bgcolor'],
            'img.margin: 10 5 30 auto (= no horizontal centering)'
                => ['<img>', 'img {margin: 10 5 30 auto;}', 'align'],
            'p.margin: auto'
                => ['<p>Bonjour</p>', 'p {margin: auto;}', 'align'],
            'p.border: none'
                => ['<p>Bonjour</p>', 'p {border: none;}', 'border'],
            'img.border: 1px solid black'
                => ['<p>Bonjour</p>', 'p {border: 1px solid black;}', 'border'],
            'span.text-align'
                => ['<span>hi</span>', 'span {text-align: justify;}', 'align'],
            'text-align: inherit'
                => ['<p>hi</p>', 'p {text-align: inherit;}', 'align'],
            'span.float'
                => ['<span>hi</span>', 'span {float: right;}', 'align'],
            'float: none'
                => ['<table></table>', 'table {float: none;}', 'align'],
            'p.border-spacing'
                => ['<p>Hello</p>', 'p {border-spacing: 5px;}', 'cellspacing'],
        ];
    }

    /**
     * @test
     * @param string $body      the HTML
     * @param string $css       the complete CSS
     * @param string $attribute the attribute that must not be present on this element
     *
     * @dataProvider notMatchingCssToHtmlMappingDataProvider
     */
    public function emogrifierNotMapsUnsuitableCssToHtmlIfFeatureIsEnabled($body, $css, $attribute)
    {
        $this->subject->setHtml($this->html5DocumentType . '<html><body>' . $body . '</body></html>');
        $this->subject->setCss($css);
        $this->subject->enableCssToHtmlMapping();

        $html = $this->subject->emogrify();

        self::assertNotContains(
            $attribute . '="',
            $html
        );
    }

    /**
     * @test
     */
    public function emogrifierNotMapsCssToHtmlIfFeatureIsNotEnabled()
    {
        $css = 'img {float: right;}';
        $this->subject->setHtml($this->html5DocumentType . '<html><body><img></body></html>');
        $this->subject->setCss($css);

        $html = $this->subject->emogrify();

        self::assertNotContains(
            '<img align="right',
            $html
        );
    }
}
