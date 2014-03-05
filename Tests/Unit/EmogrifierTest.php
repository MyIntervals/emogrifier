<?php
namespace Pelago\Tests\Unit;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class EmogrifierTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var string
     */
    const LF = "\n";

    /**
     * @var string
     */
    const HTML4_TRANSITIONAL_DOCUMENT_TYPE = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">';

    /**
     * @var string
     */
    const XHTML1_STRICT_DOCUMENT_TYPE = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';

    /**
    * @var string
    */
    const HTML5_DOCUMENT_TYPE = '<!DOCTYPE html>';

    /**
     * @var \Pelago\Emogrifier
     */
    private $subject = NULL;

    /**
     * This method is called before the first test of this test class is run.
     *
     * @return void
     */
    public static function setUpBeforeClass() {
        require_once(__DIR__ . '/../../Classes/Emogrifier.php');
    }

    /**
     * Sets up the test case.
     *
     * @return void
     */
    protected function setUp() {
        $this->subject = new \Pelago\Emogrifier();
    }

    /**
     * Tear down.
     *
     * @return void
     */
    protected function tearDown() {
        unset($this->subject);
    }

    /**
     * @test
     *
     * @expectedException BadMethodCallException
     */
    public function emogrifyForNoDataSetReturnsThrowsException() {
        $this->subject->emogrify();
    }

    /**
     * @test
     *
     * @expectedException BadMethodCallException
     */
    public function emogrifyForEmptyHtmlAndEmptyCssThrowsException() {
        $this->subject->setHtml('');
        $this->subject->setCss('');

        $this->subject->emogrify();
    }

    /**
     * @test
     */
    public function emogrifyByDefaultEncodesUmlautsAsHtmlEntities() {
        $html = self::HTML5_DOCUMENT_TYPE . '<html><p>Einen schönen Gruß!</p></html>';
        $this->subject->setHtml($html);

        $this->assertContains(
            'Einen sch&ouml;nen Gru&szlig;!',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyCanKeepEncodedUmlauts() {
        $this->subject->preserveEncoding = TRUE;
        $encodedString = 'Küss die Hand, schöne Frau.';

        $html = self::HTML5_DOCUMENT_TYPE . '<html><p>' . $encodedString . '</p></html>';
        $this->subject->setHtml($html);

        $this->assertContains(
            $encodedString,
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyForHtmlTagOnlyAndEmptyCssReturnsHtmlTagWithHtml4DocumentType() {
        $html = '<html></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss('');

        $this->assertSame(
            self::HTML4_TRANSITIONAL_DOCUMENT_TYPE . self::LF . $html . self::LF,
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyForHtmlTagWithXhtml1StrictDocumentTypeKeepsDocumentType() {
        $html = self::XHTML1_STRICT_DOCUMENT_TYPE . self::LF . '<html></html>' . self::LF;
        $this->subject->setHtml($html);
        $this->subject->setCss('');

        $this->assertSame(
            $html,
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyForHtmlTagWithXhtml5DocumentTypeKeepsDocumentType() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html></html>' . self::LF;
        $this->subject->setHtml($html);
        $this->subject->setCss('');

        $this->assertSame(
            $html,
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyByDefaultRemovesWbrTag() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html>foo<wbr/>bar</html>' . self::LF;
        $this->subject->setHtml($html);

        $this->assertContains(
            'foobar',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function addUnprocessableTagCausesTagToBeRemoved() {
        $this->subject->addUnprocessableHtmlTag('p');

        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html><p></p></html>' . self::LF;
        $this->subject->setHtml($html);

        $this->assertNotContains(
            '<p>',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function addUnprocessableTagNotRemovesTagWithContent() {
        $this->subject->addUnprocessableHtmlTag('p');

        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html><p>foobar</p></html>' . self::LF;
        $this->subject->setHtml($html);

        $this->assertContains(
            '<p>',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function removeUnprocessableHtmlTagCausesTagToStayAgain() {
        $this->subject->addUnprocessableHtmlTag('p');
        $this->subject->removeUnprocessableHtmlTag('p');

        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html><p>foo<br/><span>bar</span></p></html>' . self::LF;
        $this->subject->setHtml($html);

        $this->assertContains(
            '<p>',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyCanAddMatchingElementRuleOnHtmlElementFromCss() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html></html>' . self::LF;
        $this->subject->setHtml($html);
        $styleRule = 'color: #000;';
        $this->subject->setCss('html {' . $styleRule . '}');

        $this->assertContains(
            '<html style="' . $styleRule . '">',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyNotAddsNotMatchingElementRuleOnHtmlElementFromCss() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html></html>' . self::LF;
        $this->subject->setHtml($html);
        $this->subject->setCss('p {color: #000;}');

        $this->assertContains(
            '<html>',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyCanMatchTwoElements() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html><p></p><p></p></html>' . self::LF;
        $this->subject->setHtml($html);
        $styleRule = 'color: #000;';
        $this->subject->setCss('p {' . $styleRule . '}');

        $this->assertSame(
            2,
            substr_count($this->subject->emogrify(), '<p style="' . $styleRule . '">')
        );
    }

    /**
     * @test
     */
    public function emogrifyCanAssignTwoStyleRulesFromSameMatcherToElement() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html><p></p></html>' . self::LF;
        $this->subject->setHtml($html);
        $styleRules = 'color: #000; text-align: left;';
        $this->subject->setCss('p {' . $styleRules . '}');

        $this->assertContains(
            '<p style="' . $styleRules . '">',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyCanAssignStyleRulesFromTwoIdenticalMatchersToElement() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html><p></p></html>' . self::LF;
        $this->subject->setHtml($html);
        $styleRule1 = 'color:#000;';
        $styleRule2 = 'text-align:left;';
        $this->subject->setCss('p {' . $styleRule1 . '}  p {' . $styleRule2 . '}');

        $this->assertContains(
            '<p style="' . $styleRule1 . $styleRule2 . '">',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyCanAssignStyleRulesFromTwoDifferentMatchersToElement() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html><p class="x"></p></html>' . self::LF;
        $this->subject->setHtml($html);
        $styleRule1 = 'color:#000;';
        $styleRule2 = 'text-align:left;';
        $this->subject->setCss('p {' . $styleRule1 . '}  .x {' . $styleRule2 . '}');

        $this->assertContains(
            '<p class="x" style="' . $styleRule1 . $styleRule2 . '">',
            $this->subject->emogrify()
        );
    }

    /**
     * Data provide for selectors.
     *
     * @return array
     */
    public function selectorDataProvider() {
        $styleRule = 'color: red;';
        $styleAttribute = 'style="' . $styleRule . '"';

        return array(
            'universal selector HTML' => array('* {' . $styleRule . '} ', '#<html id="html" ' . $styleAttribute . '>#'),
            'universal selector BODY' => array('* {' . $styleRule . '} ', '#<body ' . $styleAttribute . '>#'),
            'universal selector P' => array('* {' . $styleRule . '} ', '#<p[^>]*' . $styleAttribute . '>#'),
            'type selector matches first P' => array('p {' . $styleRule . '} ', '#<p class="p-1" ' . $styleAttribute . '>#'),
            'type selector matches second P' => array('p {' . $styleRule . '} ', '#<p class="p-2" ' . $styleAttribute . '>#'),
            'descendant selector P SPAN' => array('p span {' . $styleRule . '} ', '#<span ' . $styleAttribute . '>#'),
            'descendant selector BODY SPAN' => array('body span {' . $styleRule . '} ', '#<span ' . $styleAttribute . '>#'),
            'child selector P > SPAN matches direct child'
                => array('p > span {' . $styleRule . '} ', '#<span ' . $styleAttribute . '>#'),
            'child selector BODY > SPAN not matches grandchild' => array('body > span {' . $styleRule . '} ', '#<span>#'),
            'adjacent selector P + P not matches first P' => array('p + p {' . $styleRule . '} ', '#<p class="p-1">#'),
            'adjacent selector P + P matches second P'
                => array('p + p {' . $styleRule . '} ', '#<p class="p-2" style="' . $styleRule . '">#'),
            'adjacent selector P + P matches third P'
                => array('p + p {' . $styleRule . '} ', '#<p class="p-3" style="' . $styleRule . '">#'),
            'ID selector #HTML' => array('#html {' . $styleRule . '} ', '#<html id="html" ' . $styleAttribute . '>#'),
            'type and ID selector HTML#HTML'
                => array('html#html {' . $styleRule . '} ', '#<html id="html" ' . $styleAttribute . '>#'),
            'class selector .P-1' => array('.p-1 {' . $styleRule . '} ', '#<p class="p-1" ' . $styleAttribute . '>#'),
            'type and class selector P.P-1' => array('p.p-1 {' . $styleRule . '} ', '#<p class="p-1" ' . $styleAttribute . '>#'),
            'attribute presence selector SPAN[title] matches element with matching attribute'
                => array('span[title] {' . $styleRule . '} ', '#<span title="bonjour" ' . $styleAttribute . '>#'),
            'attribute presence selector SPAN[title] not matches element without any attributes'
                => array('span[title] {' . $styleRule . '} ', '#<span>#'),
            'attribute value selector SPAN[title] matches element with matching attribute value'
                => array('span[title="bonjour"] {' . $styleRule . '} ', '#<span title="bonjour" ' . $styleAttribute . '>#'),
            'attribute value selector SPAN[title] not matches element with other attribute value'
                => array('span[title="bonjour"] {' . $styleRule . '} ', '#<span title="buenas dias">#'),
            'attribute value selector SPAN[title] not matches element without any attributes'
                => array('span[title="bonjour"] {' . $styleRule . '} ', '#<span>#'),
        );
    }

    /**
     * @test
     *
     * @param string $css the complete CSS
     * @param string $containedHtml regular expression for the the HTML that needs to be contained in the merged HTML
     *
     * @dataProvider selectorDataProvider
     */
    public function emogrifierMatchesSelectors($css, $containedHtml) {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF .
            '<html id="html">' .
            '  <body>' .
            '    <p class="p-1"><span>some text</span></p>' .
            '    <p class="p-2"><span title="bonjour">some</span> text</p>' .
            '    <p class="p-3"><span title="buenas dias">some</span> more text</p>' .
            '  </body>' .
            '</html>';

        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $this->assertRegExp(
            $containedHtml,
            $this->subject->emogrify()
        );
    }

    /**
     * Data provider for emogrifyDropsWhitespaceFromCssDeclarations.
     *
     * @return array
     */
    public function cssDeclarationWhitespaceDroppingDataProvider() {
        return array(
            'no whitespace, trailing semicolon' => array('color:#000;', 'color:#000;'),
            'no whitespace, no trailing semicolon' => array('color:#000', 'color:#000'),
            'space after colon, no trailing semicolon' => array('color: #000', 'color: #000'),
            'space before colon, no trailing semicolon' => array('color :#000', 'color :#000'),
            'space before property name, no trailing semicolon' => array(' color:#000', 'color:#000'),
            'space before trailing semicolon' => array(' color:#000 ;', 'color:#000 ;'),
            'space after trailing semicolon' => array(' color:#000; ', 'color:#000;'),
            'space after property value, no trailing semicolon' => array(' color:#000; ', 'color:#000;'),
        );
    }

    /**
     * @test
     *
     * @param string $cssDeclarationBlock the CSS declaration block (without the curly braces)
     * @param string $expectedStyleAttributeContent the expected value of the style attribute
     *
     * @dataProvider cssDeclarationWhitespaceDroppingDataProvider
     */
    public function emogrifyDropsLeadingAndTrailingWhitespaceFromCssDeclarations(
        $cssDeclarationBlock, $expectedStyleAttributeContent
    ) {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html></html>';
        $css = 'html {' . $cssDeclarationBlock . '}';

        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $this->assertContains(
            'html style="' . $expectedStyleAttributeContent . '">',
            $this->subject->emogrify()
        );
    }

    /**
     * Data provider for emogrifyFormatsCssDeclarations.
     *
     * @return array
     */
    public function formattedCssDeclarationDataProvider() {
        return array(
            'one declaration' => array('color: #000;', 'color: #000;'),
            'one declaration with dash in property name' => array('font-weight: bold;', 'font-weight: bold;'),
            'one declaration with space in property value' => array('margin: 0 4px;', 'margin: 0 4px;'),
            'two declarations separated by semicolon' => array('color: #000;width: 3px;', 'color: #000;width: 3px;'),
            'two declarations separated by semicolon and space' => array('color: #000; width: 3px;', 'color: #000; width: 3px;'),
            'two declaration separated by semicolon and Linefeed' => array(
                'color: #000;' . self::LF . 'width: 3px;', 'color: #000;' . self::LF . 'width: 3px;'
            ),
        );
    }

    /**
     * @test
     *
     * @param string $cssDeclarationBlock the CSS declaration block (without the curly braces)
     * @param string $expectedStyleAttributeContent the expected value of the style attribute
     *
     * @dataProvider formattedCssDeclarationDataProvider
     */
    public function emogrifyFormatsCssDeclarations(
        $cssDeclarationBlock, $expectedStyleAttributeContent
    ) {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF .
            '<html></html>';
        $css = 'html {' . $cssDeclarationBlock . '}';

        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $this->assertContains(
            'html style="' . $expectedStyleAttributeContent . '">',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyKeepsExistingStyleAttributes() {
        $styleAttribute = 'style="color:#ccc;"';
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html ' . $styleAttribute . '></html>';
        $this->subject->setHtml($html);

        $this->assertContains(
            $styleAttribute,
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyAddsCssAfterExistingStyle() {
        $styleAttributeValue = 'color:#ccc;';
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html style="' . $styleAttributeValue . '"></html>';
        $this->subject->setHtml($html);

        $cssDeclarations = 'margin:0 2px;';
        $css = 'html {' . $cssDeclarations . '}';
        $this->subject->setCss($css);

        $this->assertContains(
            'style="' . $styleAttributeValue . $cssDeclarations . '"',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyCanMatchMinifiedCss() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html><p></p></html>' . self::LF;
        $this->subject->setHtml($html);
        $this->subject->setCss('p{color:blue;}html{color:red;}');

        $this->assertContains(
            '<html style="color:red;">',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyLowercasesAttributeNamesFromStyleAttributes() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html style="COLOR:#ccc;"></html>';
        $this->subject->setHtml($html);

        $this->assertContains(
            'style="color:#ccc;"',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyKeepsAttributeNamesFromCssInOriginalCasing() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html></html>';
        $this->subject->setHtml($html);

        $cssDeclarations = 'mArGiN:0 2px;';
        $css = 'html {' . $cssDeclarations . '}';
        $this->subject->setCss($css);

        $this->assertContains(
            'style="' . $cssDeclarations . '"',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyRemovesStyleNodes() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html><style type="text/css"></style></html>';
        $this->subject->setHtml($html);

        $this->assertNotContains(
            '<style>',
            $this->subject->emogrify()
        );
    }

    /**
     * Data provider for things that should be left out when applying the CSS.
     *
     * @return array<array>
     */
    public function unneededCssThingsDataProvider() {
        return array(
            'CSS comments with one asterisk' => array('p {color: #000;/* black */}', 'black'),
            'CSS comments with two asterisks' => array('p {color: #000;/** black */}', 'black'),
            '@import directive' => array('@import "foo.css";', '@import'),
            'style in "aural" media type rule' => array('@media aural {p {color: #000;}}', '#000'),
            'style in "braille" media type rule' => array('@media braille {p {color: #000;}}', '#000'),
            'style in "embossed" media type rule' => array('@media embossed {p {color: #000;}}', '#000'),
            'style in "handheld" media type rule' => array('@media handheld {p {color: #000;}}', '#000'),
            'style in "print" media type rule' => array('@media print {p {color: #000;}}', '#000'),
            'style in "projection" media type rule' => array('@media projection {p {color: #000;}}', '#000'),
            'style in "speech" media type rule' => array('@media speech {p {color: #000;}}', '#000'),
            'style in "tty" media type rule' => array('@media tty {p {color: #000;}}', '#000'),
            'style in "tv" media type rule' => array('@media tv {p {color: #000;}}', '#000'),
        );
    }

    /**
     * @test
     *
     * @param string $css
     * @param string $markerNotExpectedInHtml
     *
     * @dataProvider unneededCssThingsDataProvider
     */
    public function emogrifyFiltersUnneededCssThings($css, $markerNotExpectedInHtml) {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html><p>foo</p></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $this->assertNotContains(
            $markerNotExpectedInHtml,
            $this->subject->emogrify()
        );
    }

    /**
     * Data provider for media rules.
     *
     * @return array<array>
     */
    public function mediaRulesDataProvider() {
        return array(
            'style in "screen" media type rule' => array('@media screen {p {color: #000;}}', '<p style="color: #000;">'),
            'style in "all" media type rule' => array('@media all {p {color: #000;}}', '<p style="color: #000;">'),
        );
    }

    /**
     * @test
     *
     * @param string $css
     * @param string $markerExpectedInHtml
     *
     * @dataProvider mediaRulesDataProvider
     */
    public function emogrifyKeepsStylesInApplicableMediaRules($css, $markerExpectedInHtml) {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF . '<html><p>foo</p></html>';
        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $this->assertContains(
            $markerExpectedInHtml,
            $this->subject->emogrify()
        );
    }


    /**
     * @test
     */
    public function emogrifyAppliesCssFromStyleNodes() {
        $styleAttributeValue = 'color:#ccc;';
        $html = self::HTML5_DOCUMENT_TYPE . self::LF .
        '<html><style type="text/css">html {' . $styleAttributeValue . '}</style></html>';
        $this->subject->setHtml($html);

        $this->assertContains(
            '<html style="' . $styleAttributeValue . '">',
            $this->subject->emogrify()
        );
    }
}
