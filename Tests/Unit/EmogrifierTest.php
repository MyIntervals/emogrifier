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
     * @var Emogrifier
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
     *
     * @see emogrifierMatchesSelectors
     */
    public function selectorDataProvider() {
        $styleRule = 'color: red';
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
            'BODY:first-child not matches second child' => array('body:first-child {' . $styleRule . '} ', '#<p class="p-2">#'),
            'ID selector #HTML' => array('#html {' . $styleRule . '} ', '#<html id="html" ' . $styleAttribute . '>#'),
            'type and ID selector HTML#HTML'
                => array('html#html {' . $styleRule . '} ', '#<html id="html" ' . $styleAttribute . '>#'),
            'class selector .P-1' => array('.p-1 {' . $styleRule . '} ', '#<p class="p-1" ' . $styleAttribute . '>#'),
            'type and class selector P.P-1' => array('p.p-1 {' . $styleRule . '} ', '#<p class="p-1" ' . $styleAttribute . '>#'),
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
            '    <p class="p-2">some text</p>' .
            '  </body>' .
            '</html>';

        $this->subject->setHtml($html);
        $this->subject->setCss($css);

        $this->assertRegExp(
            $containedHtml,
            $this->subject->emogrify()
        );
    }
}
