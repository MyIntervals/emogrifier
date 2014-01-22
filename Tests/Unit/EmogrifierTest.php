<?php
/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class EmogrifierTest extends PHPUnit_Framework_TestCase {
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
    static public function setUpBeforeClass() {
        require_once(__DIR__ . '/../../emogrifier.php');
    }

    /**
     * Sets up the test case.
     *
     * @return void
     */
    protected function setUp() {
        $this->subject = new Emogrifier();
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
        $html = self::HTML5_DOCUMENT_TYPE . self::LF  . '<html></html>' . self::LF;
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
        $html = self::HTML5_DOCUMENT_TYPE . self::LF  . '<html></html>' . self::LF;
        $this->subject->setHtml($html);
        $this->subject->setCss('html {color: #000;}');

        $this->assertContains(
            '<html style="color: #000;">',
            $this->subject->emogrify()
        );
    }

    /**
     * @test
     */
    public function emogrifyNotAddsNotMatchingElementRuleOnHtmlElementFromCss() {
        $html = self::HTML5_DOCUMENT_TYPE . self::LF  . '<html></html>' . self::LF;
        $this->subject->setHtml($html);
        $this->subject->setCss('p {color: #000;}');

        $this->assertContains(
            '<html>',
            $this->subject->emogrify()
        );
    }
}