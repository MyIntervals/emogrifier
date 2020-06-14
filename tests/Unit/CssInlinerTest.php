<?php

declare(strict_types=1);

namespace Pelago\Emogrifer\Tests\Unit;

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\Tests\Support\Traits\AssertCss;
use PHPUnit\Framework\TestCase;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\SyntaxErrorException;

/**
 * Test case.
 *
 * @covers \Pelago\Emogrifier\CssInliner
 *
 * @author Oliver Klee <github@oliverklee.de>
 * @author Zoli Szabó <zoli.szabo+github@gmail.com>
 */
class CssInlinerTest extends TestCase
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
                <p class="p-7">
                    <a
                        href="https://example.org/"
                        data-ascii-1="! &quot; # $ % &amp; &#039; ( ) * + , - . / : ; &lt; = &gt; ?"
                        data-ascii-2="@ [ \\ ] ^ _ ` { | } ~"
                    >
                        <span id="example-org">link text</span>
                    </a>
                    <input disabled>
                    <input type="text">
                    <input disabled type="text" value="some anytext text">
                    <strong><em></em></strong>
                </p>
            </body>
        </html>
    ';

    /**
     * Builds a subject with the given HTML and debug mode enabled.
     *
     * @param string $html
     *
     * @return CssInliner
     */
    private function buildDebugSubject(string $html): CssInliner
    {
        $subject = CssInliner::fromHtml($html);
        $subject->setDebug(true);

        return $subject;
    }

    /**
     * @test
     */
    public function fromHtmlReturnsInstanceOfCalledClass(): void
    {
        $subject = CssInliner::fromHtml('<html></html>');

        self::assertInstanceOf(CssInliner::class, $subject);
    }

    /**
     * @test
     */
    public function isAbstractHtmlProcessor(): void
    {
        $subject = CssInliner::fromHtml('<html></html>');

        self::assertInstanceOf(AbstractHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function setDebugProvidesFluentInterface(): void
    {
        $subject = CssInliner::fromHtml('<html></html>');

        $result = $subject->setDebug(false);

        self::assertSame($subject, $result);
    }

    /**
     * @test
     */
    public function inlineCssProvidesFluentInterface(): void
    {
        $subject = CssInliner::fromHtml('<html><p>Hello world!</p></html>');

        $result = $subject->inlineCss();

        self::assertSame($subject, $result);
    }

    /**
     * @return string[][]
     */
    public function wbrTagDataProvider(): array
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
    public function inlineCssKeepsWbrTag(string $html): void
    {
        $subject = $this->buildDebugSubject($html);

        $subject->inlineCss();

        $result = $subject->renderBodyContent();
        $expectedWbrTagCount = \substr_count($html, '<wbr');
        $resultWbrTagCount = \substr_count($result, '<wbr');
        self::assertSame($expectedWbrTagCount, $resultWbrTagCount);
    }

    /**
     * @return string[][]
     */
    public function matchedCssDataProvider(): array
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
            'type (case-insensitive) & class => type with class' => ['P.p-2 { %1$s }', '<p class="p-2" style="%1$s">'],
            'ID => with ID' => ['#p4 { %1$s }', '<p class="p-4" id="p4" style="%1$s">'],
            'type & ID => type with ID' => ['p#p4 { %1$s }', '<p class="p-4" id="p4" style="%1$s">'],
            'type (case-insensitive) & ID => type with ID' => ['P#p4 { %1$s }', '<p class="p-4" id="p4" style="%1$s">'],
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
            'type & attribute presence => with type & attribute' => [
                'span[title] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
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
            'type & two-word attribute value with ^, double quotes => with exact match' => [
                'span[title^="buenas dias"] { %1$s }',
                '<span title="buenas dias" style="%1$s">',
            ],
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
            // broken: :required
            // broken: :optional
            'adjacent => 2nd of many' => ['p + p { %1$s }', '<p class="p-2" style="%1$s">'],
            'adjacent => last of many' => ['p + p { %1$s }', '<p class="p-7" style="%1$s">'],
            'adjacent (without space after +) => last of many' => ['p +p { %1$s }', '<p class="p-7" style="%1$s">'],
            'adjacent (without space before +) => last of many' => ['p+ p { %1$s }', '<p class="p-7" style="%1$s">'],
            'adjacent (without space before or after +) => last of many' => [
                'p+p { %1$s }',
                '<p class="p-7" style="%1$s">',
            ],
            'general sibling => 2nd of many' => ['.p-1 ~ p { %1$s }', '<p class="p-2" style="%1$s">'],
            'general sibling => last of many' => ['.p-1 ~ p { %1$s }', '<p class="p-7" style="%1$s">'],
            'general sibling (without space after ~) => last of many' => [
                '.p-1 ~p { %1$s }',
                '<p class="p-7" style="%1$s">',
            ],
            'general sibling (without space before ~) => last of many' => [
                '.p-1~ p { %1$s }',
                '<p class="p-7" style="%1$s">',
            ],
            'general sibling (without space before or after ~) => last of many' => [
                '.p-1~p { %1$s }',
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
            'universal general sibling => 2nd of many' => ['.p-1 ~ * { %1$s }', '<p class="p-2" style="%1$s">'],
            'universal general sibling => last of many' => ['.p-1 ~ * { %1$s }', '<p class="p-7" style="%1$s">'],
            'general sibling of universal => 2nd of many' => ['* ~ p { %1$s }', '<p class="p-2" style="%1$s">'],
            'general sibling of universal => last of many' => ['* ~ p { %1$s }', '<p class="p-7" style="%1$s">'],
            'child universal => direct child' => ['body > * { %1$s }', '<p class="p-1" style="%1$s">'],
            'child of universal => direct child' => ['* > body { %1$s }', '<body style="%1$s">'],
            'descendent universal => child' => ['p * { %1$s }', '<span style="%1$s">'],
            'descendent universal => grandchild' => ['body * { %1$s }', '<span style="%1$s">'],
            'descendant of universal => child' => ['* body { %1$s }', '<body style="%1$s">'],
            'descendant of universal => grandchild' => ['* p { %1$s }', '<p class="p-1" style="%1$s">'],
            'descendent attribute presence => with attribute' => [
                'body [title] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'descendent attribute exact value => with exact attribute match' => [
                'body [title="bonjour"] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'descendent type & attribute presence => with type & attribute' => [
                'body span[title] { %1$s }',
                '<span title="bonjour" style="%1$s">',
            ],
            'descendent type & Boolean attribute presence => with type & attribute' => [
                'html input[disabled] { %1$s }',
                '<input disabled style="%1$s">',
            ],
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
            'descendant of type & attribute presence => parent with type & attribute' => [
                'p[id] span { %1$s }',
                '<p class="p-4" id="p4"><span title="avez-vous" style="%1$s">',
            ],
            'descendant of type & attribute exact value => parent with type & exact attribute match' => [
                'p[id="p4"] span { %1$s }',
                '<p class="p-4" id="p4"><span title="avez-vous" style="%1$s">',
            ],
            'descendant of type & attribute exact two-word value => parent with type & exact attribute match' => [
                'p[class="p-5 additional-class"] span { %1$s }',
                '<p class="p-5 additional-class"><span title="buenas dias bom dia" style="%1$s">',
            ],
            'descendant of type & attribute value with ~ => parent with type & exact attribute match' => [
                'p[class~="p-1"] span { %1$s }',
                '<p class="p-1"><span style="%1$s">',
            ],
            'descendant of type & attribute value with ~ => parent with type & word as 1st of 2 in attribute' => [
                'p[class~="p-5"] span { %1$s }',
                '<p class="p-5 additional-class"><span title="buenas dias bom dia" style="%1$s">',
            ],
            'child of attribute presence with - in name => child of parent with expected attribute' => [
                'a[data-ascii-1] > #example-org { %1$s }',
                '<span id="example-org" style="%1$s">',
            ],
            'child of attribute value with ^ matching : => child of parent with expected prefix match' => [
                'a[href^="https:"] > #example-org { %1$s }',
                '<span id="example-org" style="%1$s">',
            ],
            'child of attribute value with ~ matching - => child of parent with expected word match' => [
                'a[data-ascii-1~="-"] > #example-org { %1$s }',
                '<span id="example-org" style="%1$s">',
            ],
            'child of attribute value with * matching - => child of parent with expected part match' => [
                'a[data-ascii-1*="-"] > #example-org { %1$s }',
                '<span id="example-org" style="%1$s">',
            ],
            'child of attribute value with ~ matching ; => child of parent with expected word match' => [
                'a[data-ascii-1~=";"] > #example-org { %1$s }',
                '<span id="example-org" style="%1$s">',
            ],
            'child of attribute value with * matching ; => child of parent with expected part match' => [
                'a[data-ascii-1*=";"] > #example-org { %1$s }',
                '<span id="example-org" style="%1$s">',
            ],
            'type, attribute exact value & Boolean attribute presence => with exact attribute match' => [
                'input[type="text"][disabled] { %1$s }',
                '<input disabled type="text" value="some anytext text" style="%1$s">',
            ],
            'type, attribute value with * & attribute exact value => with exact attribute match' => [
                'input[value*="anytext"][type="text"] { %1$s }',
                '<input disabled type="text" value="some anytext text" style="%1$s">',
            ],
            'descendant of universal, type, attribute value with * & attribute exact value => with attribute match' => [
                '* input[value*="anytext"][type="text"] { %1$s }',
                '<input disabled type="text" value="some anytext text" style="%1$s">',
            ],
            ':first-child => 1st of many' => [':first-child { %1$s }', '<p class="p-1" style="%1$s">'],
            'type & :first-child => 1st of many' => ['p:first-child { %1$s }', '<p class="p-1" style="%1$s">'],
            'child combinator & :first-child => 1st of many' => [
                'body > :first-child { %1$s }',
                '<p class="p-1" style="%1$s">',
            ],
            ':last-child => last of many' => [':last-child { %1$s }', '<p class="p-7" style="%1$s">'],
            'type & :last-child => last of many' => ['p:last-child { %1$s }', '<p class="p-7" style="%1$s">'],
            'child combinator & :last-child => last of many' => [
                'body > :last-child { %1$s }',
                '<p class="p-7" style="%1$s">',
            ],
            ':only-child => element without siblings' => [':only-child { %1$s }', '<span style="%1$s">'],
            'type & :only-child => element without siblings' => ['span:only-child { %1$s }', '<span style="%1$s">'],
            'child combinator & :only-child => element without siblings' => [
                'p > :only-child { %1$s }',
                '<span style="%1$s">',
            ],
            ':nth-child(even) => 2nd of many' => [':nth-child(even) { %1$s }', '<p class="p-2" style="%1$s">'],
            ':nth-child(even) => 4th of many' => [':nth-child(even) { %1$s }', '<p class="p-4" id="p4" style="%1$s">'],
            ':nth-child(2n) => 2nd of many' => [':nth-child(2n) { %1$s }', '<p class="p-2" style="%1$s">'],
            ':nth-child(2n) => 4th of many' => [':nth-child(2n) { %1$s }', '<p class="p-4" id="p4" style="%1$s">'],
            ':nth-child(3) => 3rd of many' => [':nth-child(3) { %1$s }', '<div class="div-3" style="%1$s">'],
            ':nth-child(2n+3) => 3rd of many' => [':nth-child(2n+3) { %1$s }', '<div class="div-3" style="%1$s">'],
            ':nth-child(2n+3) => 5th of many' => [
                ':nth-child(2n+3) { %1$s }',
                '<p class="p-5 additional-class" style="%1$s">',
            ],
            ':nth-child(-n+3) => 2nd of many' => [':nth-child(-n+3) { %1$s }', '<p class="p-2" style="%1$s">'],
            ':nth-child(-n+3) => 3rd of many' => [':nth-child(-n+3) { %1$s }', '<div class="div-3" style="%1$s">'],
            'type & :nth-child(even) => 2nd of many' => ['p:nth-child(even) { %1$s }', '<p class="p-2" style="%1$s">'],
            ':nth-last-child(even) => 2nd last of many' => [
                ':nth-last-child(even) { %1$s }',
                '<p class="p-6" style="%1$s">',
            ],
            ':nth-last-child(even) => 4th last of many' => [
                ':nth-last-child(even) { %1$s }',
                '<p class="p-4" id="p4" style="%1$s">',
            ],
            ':nth-last-child(2n) => 2nd last of many' => [
                ':nth-last-child(2n) { %1$s }',
                '<p class="p-6" style="%1$s">',
            ],
            ':nth-last-child(2n) => 4th last of many' => [
                ':nth-last-child(2n) { %1$s }',
                '<p class="p-4" id="p4" style="%1$s">',
            ],
            ':nth-last-child(3) => 3rd last of many' => [
                ':nth-last-child(3) { %1$s }',
                '<p class="p-5 additional-class" style="%1$s">',
            ],
            ':nth-last-child(2n+3) => 3rd last of many' => [
                ':nth-last-child(2n+3) { %1$s }',
                '<p class="p-5 additional-class" style="%1$s">',
            ],
            ':nth-last-child(2n+3) => 5th last of many' => [
                ':nth-last-child(2n+3) { %1$s }',
                '<div class="div-3" style="%1$s">',
            ],
            ':nth-last-child(-n+3) => 2nd last of many' => [
                ':nth-last-child(-n+3) { %1$s }',
                '<p class="p-6" style="%1$s">',
            ],
            ':nth-last-child(-n+3) => 3rd last of many' => [
                ':nth-last-child(-n+3) { %1$s }',
                '<p class="p-5 additional-class" style="%1$s">',
            ],
            'type & :nth-last-child(even) => 2nd last of many' => [
                'p:nth-last-child(even) { %1$s }',
                '<p class="p-6" style="%1$s">',
            ],
            // broken: first-of-type without preceding type
            'type & :first-of-type => 1st of many' => ['p:first-of-type { %1$s }', '<p class="p-1" style="%1$s">'],
            'type & :first-of-type => 1st of that type' => [
                'div:first-of-type { %1$s }',
                '<div class="div-3" style="%1$s">',
            ],
            // broken: last-of-type without preceding type
            'type & :last-of-type => last of many' => ['p:last-of-type { %1$s }', '<p class="p-7" style="%1$s">'],
            'type & :last-of-type => last of that type' => [
                'div:last-of-type { %1$s }',
                '<div class="div-3" style="%1$s">',
            ],
            // broken: only-of-type without preceding type
            'type & :only-of-type => only child' => ['span:only-of-type { %1$s }', '<span style="%1$s">'],
            'type & :only-of-type => only of that type' => [
                'div:only-of-type { %1$s }',
                '<div class="div-3" style="%1$s">',
            ],
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
            'type & :nth-of-type(2n+3) => 3rd of many of type' => [
                'p:nth-of-type(2n+3) { %1$s }',
                '<p class="p-4" id="p4" style="%1$s">',
            ],
            'type & :nth-of-type(2n+3) => 5th of many of type' => [
                'p:nth-of-type(2n+3) { %1$s }',
                '<p class="p-6" style="%1$s">',
            ],
            'type & :nth-of-type(-n+3) => 2nd of many of type' => [
                'p:nth-of-type(-n+3) { %1$s }',
                '<p class="p-2" style="%1$s">',
            ],
            'type & :nth-of-type(-n+3) => 3rd of many of type' => [
                'p:nth-of-type(-n+3) { %1$s }',
                '<p class="p-4" id="p4" style="%1$s">',
            ],
            // broken: nth-last-of-type without preceding type
            'type & :nth-last-of-type(even) => 2nd last of many of type' => [
                'p:nth-last-of-type(even) { %1$s }',
                '<p class="p-6" style="%1$s">',
            ],
            'type & :nth-last-of-type(even) => 4th last of many of type' => [
                'p:nth-last-of-type(even) { %1$s }',
                '<p class="p-4" id="p4" style="%1$s">',
            ],
            'type & :nth-last-of-type(2n) => 2nd last of many of type' => [
                'p:nth-last-of-type(2n) { %1$s }',
                '<p class="p-6" style="%1$s">',
            ],
            'type & :nth-last-of-type(2n) => 4th last of many of type' => [
                'p:nth-last-of-type(2n) { %1$s }',
                '<p class="p-4" id="p4" style="%1$s">',
            ],
            'type & :nth-last-of-type(3) => 3rd last of many of type' => [
                'p:nth-last-of-type(3) { %1$s }',
                '<p class="p-5 additional-class" style="%1$s">',
            ],
            'type & :nth-last-of-type(2n+3) => 3rd last of many of type' => [
                'p:nth-last-of-type(2n+3) { %1$s }',
                '<p class="p-5 additional-class" style="%1$s">',
            ],
            'type & :nth-last-of-type(2n+3) => 5th last of many of type' => [
                'p:nth-last-of-type(2n+3) { %1$s }',
                '<p class="p-2" style="%1$s">',
            ],
            'type & :nth-last-of-type(-n+3) => 2nd last of many of type' => [
                'p:nth-last-of-type(-n+3) { %1$s }',
                '<p class="p-6" style="%1$s">',
            ],
            'type & :nth-last-of-type(-n+3) => 3rd last of many of type' => [
                'p:nth-last-of-type(-n+3) { %1$s }',
                '<p class="p-5 additional-class" style="%1$s">',
            ],
            ':empty => non-void element without content' => [':empty { %1$s }', '<em style="%1$s">'],
            ':empty => void element' => [':empty { %1$s }', '<input type="text" style="%1$s">'],
            ':not with type => other type' => [':not(p) { %1$s }', '<span style="%1$s">'],
            ':not with class => no class' => [':not(.p-1) { %1$s }', '<span style="%1$s">'],
            ':not with class => other class' => [':not(.p-1) { %1$s }', '<p class="p-2" style="%1$s">'],
            'type & :not with class => without class' => ['span:not(.foo) { %1$s }', '<span style="%1$s">'],
            'type & :not with class => with other class' => ['p:not(.foo) { %1$s }', '<p class="p-1" style="%1$s">'],
            // broken: child of :any-link => child of anchor with href
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
    public function inlineCssAppliesCssToMatchingElements(string $css, string $expectedHtml): void
    {
        $cssDeclaration1 = 'color: red;';
        $cssDeclaration2 = 'text-align: left;';
        $needleExpected = \sprintf($expectedHtml, $cssDeclaration1, $cssDeclaration2);

        $subject = $this->buildDebugSubject(self::COMMON_TEST_HTML);

        $subject->inlineCss(\sprintf($css, $cssDeclaration1, $cssDeclaration2));

        $result = $subject->render();
        $selector = \trim(\strtok($css, '{'));
        $xPathExpression = (new CssSelectorConverter())->toXPath($selector);
        $message = 'with converted XPath expression `' . $xPathExpression . '`';
        self::assertContains($needleExpected, $result, $message);
    }

    /**
     * @return string[][]
     */
    public function nonMatchedCssDataProvider(): array
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
            'type & attribute presence => not element of type without that attribute' => [
                'span[title] { %1$s }',
                '<span>',
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
            'type & two word attribute value with ^ => not element with only substring match in attribute value' => [
                'span[title^="dias bom"] { %1$s }',
                '<span title="buenas dias bom dia">',
            ],
            'type & two word attribute value with ^ => not element with only suffix match in attribute value' => [
                'span[title^="bom dia"] { %1$s }',
                '<span title="buenas dias bom dia">',
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
            'general sibling => not 1st of many' => ['p ~ p { %1$s }', '<p class="p-1">'],
            'child => not grandchild' => ['html > span { %1$s }', '<span>'],
            'child => not parent' => ['span > html { %1$s }', '<html>'],
            'descendant => not sibling' => ['span span { %1$s }', '<span>'],
            'descendant => not parent' => ['p body { %1$s }', '<body>'],
            'adjacent universal => not 1st of many' => ['p + * { %1$s }', '<p class="p-1">'],
            'adjacent universal => not last of many' => ['.p-2 + * { %1$s }', '<p class="p-7">'],
            'adjacent universal => not previous of many' => ['.p-2 + * { %1$s }', '<p class="p-1">'],
            'adjacent of universal => not 1st of many' => ['* + p { %1$s }', '<p class="p-1">'],
            'universal general sibling => not 1st of many' => ['p ~ * { %1$s }', '<p class="p-1">'],
            'universal general sibling => not previous of many' => ['.p-2 ~ * { %1$s }', '<p class="p-1">'],
            'general sibling of universal => not 1st of many' => ['* ~ p { %1$s }', '<p class="p-1">'],
            'child universal => not parent' => ['body > * { %1$s }', '<html>'],
            'child universal => not self' => ['body > * { %1$s }', '<body>'],
            'child universal => not grandchild' => ['body > * { %1$s }', '<span>'],
            'child of universal => not root element' => ['* > html { %1$s }', '<html>'],
            'descendent universal => not parent' => ['p *', '<body>'],
            'descendent universal => not self' => ['p *', '<p class="p-1">'],
            'descendant of universal => not root element' => ['* html { %1$s }', '<html>'],
            'descendent type & attribute value with ^ => not element with only substring match in attribute value' => [
                'p span[title^=njo] { %1$s }',
                '<span title="bonjour">',
            ],
            'child of attribute presence with - in name => not child of parent without expected attribute' => [
                'a[data-some-other] > #example-org { %1$s }',
                '<span id="example-org">',
            ],
            'child of attribute value with ^ matching : => not child of parent without expected prefix match' => [
                'a[href^="ftp:"] > #example-org { %1$s }',
                '<span id="example-org">',
            ],
            'child of attribute value with ~ matching - => not child of parent without expected word match' => [
                'a[data-ascii-2~="-"] > #example-org { %1$s }',
                '<span id="example-org">',
            ],
            'child of attribute value with * matching - => not child of parent without expected part match' => [
                'a[data-ascii-2*="-"] > #example-org { %1$s }',
                '<span id="example-org">',
            ],
            'child of attribute value with ~ matching ; => not child of parent without expected word match' => [
                'a[data-ascii-2~=";"] > #example-org { %1$s }',
                '<span id="example-org">',
            ],
            'child of attribute value with * matching ; => not child of parent without expected part match' => [
                'a[data-ascii-2*=";"] > #example-org { %1$s }',
                '<span id="example-org">',
            ],
            'type, attribute exact value & Boolean attribute presence => not with only exact attribute match' => [
                'input[type="text"][disabled] { %1$s }',
                '<input type="text">',
            ],
            'type, attribute exact value & Boolean attribute presence => not with only attribute presence' => [
                'input[type="text"][disabled] { %1$s }',
                '<input disabled>',
            ],
            ':first-child => not 2nd of many' => [':first-child { %1$s }', '<p class="p-2">'],
            ':first-child => not last of many' => [':first-child { %1$s }', '<p class="p-7">'],
            'type & :first-child => not 2nd of many' => ['p:first-child { %1$s }', '<p class="p-2">'],
            'type & :first-child => not last of many' => ['p:first-child { %1$s }', '<p class="p-7">'],
            'child combinator & :first-child => not 2nd of many' => ['body > :first-child { %1$s }', '<p class="p-2">'],
            'child combinator & :first-child => not last of many' => [
                'body > :first-child { %1$s }',
                '<p class="p-7">',
            ],
            ':last-child => not 1st of many' => [':last-child { %1$s }', '<p class="p-1">'],
            ':last-child => not 2nd of many' => [':last-child { %1$s }', '<p class="p-2">'],
            'type & :last-child => not 1st of many' => ['p:last-child { %1$s }', '<p class="p-1">'],
            'type & :last-child => not 2nd of many' => ['p:last-child { %1$s }', '<p class="p-2">'],
            'child combinator & :last-child => not 1st of many' => ['body > :last-child { %1$s }', '<p class="p-1">'],
            'child combinator & :last-child => not 2nd of many' => ['body > :last-child { %1$s }', '<p class="p-2">'],
            ':only-child => not element with siblings' => [':only-child { %1$s }', '<p class="p-1">'],
            'type & :only-child => not element with siblings' => ['p:only-child { %1$s }', '<p class="p-1">'],
            'child combinator & :only-child => not element with siblings' => [
                'body > :only-child { %1$s }',
                '<p class="p-1">',
            ],
            ':nth-child(even) => not 1st of many' => [':nth-child(even) { %1$s }', '<p class="p-1">'],
            ':nth-child(even) => not 3rd of many' => [':nth-child(even) { %1$s }', '<div class="div-3">'],
            ':nth-child(2n) => not 1st of many' => [':nth-child(2n) { %1$s }', '<p class="p-1">'],
            ':nth-child(2n) => not 3rd of many' => [':nth-child(2n) { %1$s }', '<div class="div-3">'],
            ':nth-child(3) => not 1st of many' => [':nth-child(3) { %1$s }', '<p class="p-1">'],
            ':nth-child(3) => not 2nd of many' => [':nth-child(3) { %1$s }', '<p class="p-2">'],
            ':nth-child(3) => not 4th of many' => [':nth-child(3) { %1$s }', '<p class="p-4" id="p4">'],
            ':nth-child(3) => not 6th of many' => [':nth-child(3) { %1$s }', '<p class="p-6">'],
            ':nth-child(2n+3) => not 1st of many' => [':nth-child(2n+3) { %1$s }', '<p class="p-1">'],
            ':nth-child(2n+3) => not 4th of many' => [':nth-child(2n+3) { %1$s }', '<p class="p-4" id="p4">'],
            ':nth-child(-n+3) => not 4th of many' => [':nth-child(-n+3) { %1$s }', '<p class="p-4" id="p4">'],
            ':nth-child(-n+3) => not 5th of many' => [':nth-child(-n+3) { %1$s }', '<p class="p-5 additional-class">'],
            ':nth-last-child(even) => not last of many' => [':nth-last-child(even) { %1$s }', '<p class="p-7">'],
            ':nth-last-child(even) => not 3rd last of many' => [
                ':nth-last-child(even) { %1$s }',
                '<p class="p-5 additional-class">',
            ],
            ':nth-last-child(2n) => not last of many' => [':nth-last-child(2n) { %1$s }', '<p class="p-7">'],
            ':nth-last-child(2n) => not 3rd last of many' => [
                ':nth-last-child(2n) { %1$s }',
                '<p class="p-5 additional-class">',
            ],
            ':nth-last-child(3) => not last of many' => [':nth-last-child(3) { %1$s }', '<p class="p-7">'],
            ':nth-last-child(3) => not 2nd last of many' => [':nth-last-child(3) { %1$s }', '<p class="p-6">'],
            ':nth-last-child(3) => not 4th last of many' => [':nth-last-child(3) { %1$s }', '<p class="p-4" id="p4">'],
            ':nth-last-child(3) => not 6th last of many' => [':nth-last-child(3) { %1$s }', '<p class="p-2">'],
            ':nth-last-child(2n+3) => not last of many' => [':nth-last-child(2n+3) { %1$s }', '<p class="p-7">'],
            ':nth-last-child(2n+3) => not 4th last of many' => [
                ':nth-last-child(2n+3) { %1$s }',
                '<p class="p-4" id="p4">',
            ],
            ':nth-last-child(-n+3) => not 4th last of many' => [
                ':nth-last-child(-n+3) { %1$s }',
                '<p class="p-4" id="p4">',
            ],
            ':nth-last-child(-n+3) => not 5th last of many' => [
                ':nth-last-child(-n+3) { %1$s }',
                '<div class="div-3">',
            ],
            'type & :first-of-type => not 2nd of many' => ['p:first-of-type { %1$s }', '<p class="p-2">'],
            'type & :first-of-type => not last of many' => ['p:first-of-type { %1$s }', '<p class="p-7">'],
            'type & :last-of-type => not 1st of many' => ['p:last-of-type { %1$s }', '<p class="p-1">'],
            'type & :last-of-type => not 2nd last of many' => ['p:last-of-type { %1$s }', '<p class="p-6">'],
            'type & :only-of-type => not one of many' => ['p:only-of-type { %1$s }', '<p class="p-1">'],
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
            'type & :nth-of-type(2n+3) => not 1st of many of type' => [
                'p:nth-of-type(2n+3) { %1$s }',
                '<p class="p-1">',
            ],
            'type & :nth-of-type(2n+3) => not 4th of many of type' => [
                'p:nth-of-type(2n+3) { %1$s }',
                '<p class="p-5 additional-class">',
            ],
            'type & :nth-of-type(-n+3) => not 4th of many of type' => [
                'p:nth-of-type(-n+3) { %1$s }',
                '<p class="p-5 additional-class">',
            ],
            'type & :nth-of-type(-n+3) => not 5th of many of type' => [
                'p:nth-of-type(-n+3) { %1$s }',
                '<p class="p-6">',
            ],
            'type & :nth-last-of-type(even) => not last of many of type' => [
                'p:nth-last-of-type(even) { %1$s }',
                '<p class="p-7">',
            ],
            'type & :nth-last-of-type(even) => not 3rd last of many of type' => [
                'p:nth-last-of-type(even) { %1$s }',
                '<p class="p-5 additional-class">',
            ],
            'type & :nth-last-of-type(2n) => not last of many of type' => [
                'p:nth-last-of-type(2n) { %1$s }',
                '<p class="p-7">',
            ],
            'type & :nth-last-of-type(2n) => not 3rd last of many of type' => [
                'p:nth-last-of-type(2n) { %1$s }',
                '<p class="p-5 additional-class">',
            ],
            'type & :nth-last-of-type(3) => not last of many of type' => [
                'p:nth-last-of-type(3) { %1$s }',
                '<p class="p-7">',
            ],
            'type & :nth-last-of-type(3) => not 2nd last of many of type' => [
                'p:nth-last-of-type(3) { %1$s }',
                '<p class="p-6">',
            ],
            'type & :nth-last-of-type(3) => not 4th last of many of type' => [
                'p:nth-last-of-type(3) { %1$s }',
                '<p class="p-4" id="p4">',
            ],
            'type & :nth-last-of-type(3) => not 6th last of many of type' => [
                'p:nth-last-of-type(3) { %1$s }',
                '<p class="p-1">',
            ],
            'type & :nth-last-of-type(2n+3) => not last of many of type' => [
                'p:nth-last-of-type(2n+3) { %1$s }',
                '<p class="p-7">',
            ],
            'type & :nth-last-of-type(2n+3) => not 4th last of many of type' => [
                'p:nth-last-of-type(2n+3) { %1$s }',
                '<p class="p-4" id="p4">',
            ],
            'type & :nth-last-of-type(-n+3) => not 4th last of many of type' => [
                'p:nth-last-of-type(-n+3) { %1$s }',
                '<p class="p-4" id="p4">',
            ],
            'type & :nth-last-of-type(-n+3) => not 5th last of many of type' => [
                'p:nth-last-of-type(-n+3) { %1$s }',
                '<p class="p-2">',
            ],
            ':empty => not element with children' => [':empty { %1$s }', '<strong>'],
            ':empty => not element with content' => [':empty { %1$s }', '<span>'],
            ':not with type => not that type' => [':not(p) { %1$s }', '<p class="p-1">'],
            ':not with class => not that class' => [':not(.p-1) { %1$s }', '<p class="p-1">'],
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
    public function inlineCssNotAppliesCssToNonMatchingElements(string $css, string $expectedHtml): void
    {
        $cssDeclaration1 = 'color: red;';
        $cssDeclaration2 = 'text-align: left;';
        $subject = $this->buildDebugSubject(self::COMMON_TEST_HTML);

        $subject->inlineCss(\sprintf($css, $cssDeclaration1, $cssDeclaration2));

        self::assertContains(\sprintf($expectedHtml, $cssDeclaration1, $cssDeclaration2), $subject->render());
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
     *
     * @psalm-return array<string, array<int, string>>
     */
    public function differentCssSelectorSpecificityDataProvider(): array
    {
        /**
         * @var string[] Selectors targeting `<span id="text">` with increasing specificity
         *
         * @psalm-var array<string, string>
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
    public function inlineCssAppliesMoreSpecificCssSelectorToMatchingElements(
        string $matchedTagPart,
        string $lessSpecificSelector,
        string $moreSpecificSelector
    ): void {
        $subject = $this->buildDebugSubject(self::COMMON_TEST_HTML);

        $subject->inlineCss(
            $lessSpecificSelector . ' { color: red; } ' .
            $moreSpecificSelector . ' { color: green; } ' .
            $moreSpecificSelector . ' { background-color: green; } ' .
            $lessSpecificSelector . ' { background-color: red; }'
        );

        self::assertContains($matchedTagPart . ' style="color: green; background-color: green;"', $subject->render());
    }

    /**
     * @return string[][]
     */
    public function equalCssSelectorSpecificityDataProvider(): array
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
    public function inlineCssAppliesLaterEquallySpecificCssSelectorToMatchingElements(
        string $matchedTagPart,
        string $selector1,
        string $selector2
    ): void {
        $subject = $this->buildDebugSubject(self::COMMON_TEST_HTML);

        $subject->inlineCss(
            $selector1 . ' { color: red; } ' .
            $selector2 . ' { color: green; } ' .
            $selector2 . ' { background-color: red; } ' .
            $selector1 . ' { background-color: green; }'
        );

        self::assertContains($matchedTagPart . ' style="color: green; background-color: green;"', $subject->render());
    }

    /**
     * @return string[][]
     */
    public function cssDeclarationWhitespaceDroppingDataProvider(): array
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
    public function inlineCssTrimsWhitespaceFromCssDeclarations(string $cssDeclaration): void
    {
        $subject = $this->buildDebugSubject('<html></html>');

        $subject->inlineCss('html {' . $cssDeclaration . '}');

        self::assertContains('<html style="color: #000;">', $subject->render());
    }

    /**
     * @return string[][]
     */
    public function formattedCssDeclarationDataProvider(): array
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
    public function inlineCssFormatsCssDeclarations(
        string $cssDeclarationBlock,
        string $expectedStyleAttributeContent
    ): void {
        $subject = $this->buildDebugSubject('<html></html>');

        $subject->inlineCss('html {' . $cssDeclarationBlock . '}');

        self::assertContains('<html style="' . $expectedStyleAttributeContent . '">', $subject->render());
    }

    /**
     * @return string[][]
     */
    public function invalidDeclarationDataProvider(): array
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
    public function inlineCssDropsInvalidCssDeclaration(string $cssDeclarationBlock): void
    {
        $subject = $this->buildDebugSubject('<html></html>');

        $subject->inlineCss('html {' . $cssDeclarationBlock . '}');

        self::assertContains('<html>', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssKeepsExistingStyleAttributes(): void
    {
        $styleAttribute = 'style="color: #ccc;"';
        $subject = $this->buildDebugSubject('<html ' . $styleAttribute . '></html>');

        $subject->inlineCss();

        self::assertContains($styleAttribute, $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssAddsNewCssBeforeExistingStyle(): void
    {
        $styleAttributeValue = 'color: #ccc;';
        $subject = $this->buildDebugSubject('<html style="' . $styleAttributeValue . '"></html>');
        $cssDeclarations = 'margin: 0 2px;';
        $css = 'html {' . $cssDeclarations . '}';

        $subject->inlineCss($css);

        self::assertContains('style="' . $cssDeclarations . ' ' . $styleAttributeValue . '"', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssCanMatchMinifiedCss(): void
    {
        $subject = $this->buildDebugSubject('<html><p></p></html>');

        $subject->inlineCss('p{color:blue;}html{color:red;}');

        self::assertContains('<html style="color: red;">', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssLowercasesAttributeNamesFromStyleAttributes(): void
    {
        $subject = $this->buildDebugSubject('<html style="COLOR:#ccc;"></html>');

        $subject->inlineCss();

        self::assertContains('style="color: #ccc;"', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssLowercasesAttributeNamesFromPassedInCss(): void
    {
        $subject = $this->buildDebugSubject('<html></html>');

        $subject->inlineCss('html {mArGiN:0 2pX;}');

        self::assertContains('style="margin: 0 2pX;"', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssPreservesCaseForAttributeValuesFromPassedInCss(): void
    {
        $cssDeclaration = "content: 'Hello World';";
        $subject = $this->buildDebugSubject('<html><body><p>target</p></body></html>');

        $subject->inlineCss('p {' . $cssDeclaration . '}');

        self::assertContains('<p style="' . $cssDeclaration . '">target</p>', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssPreservesCaseForAttributeValuesFromParsedStyleBlock(): void
    {
        $cssDeclaration = "content: 'Hello World';";
        $subject = $this->buildDebugSubject(
            '<html><head><style>p {' . $cssDeclaration . '}</style></head><body><p>target</p></body></html>'
        );

        $subject->inlineCss();

        self::assertContains('<p style="' . $cssDeclaration . '">target</p>', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssRemovesStyleNodes(): void
    {
        $subject = $this->buildDebugSubject('<html><style type="text/css"></style></html>');

        $subject->inlineCss();

        self::assertNotContains('<style', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssInDebugModeForInvalidCssSelectorThrowsException(): void
    {
        $this->expectException(SyntaxErrorException::class);

        $subject = CssInliner::fromHtml(
            '<html><style type="text/css">p{color:red;} <style data-x="1">html{cursor:text;}</style></html>'
        );
        $subject->setDebug(true);

        $subject->inlineCss();
    }

    /**
     * @test
     */
    public function inlineCssNotInDebugModeIgnoresInvalidCssSelectors(): void
    {
        $html = '<html><style type="text/css">' .
            'p{color:red;} <style data-x="1">html{cursor:text;} p{background-color:blue;}</style> ' .
            '<body><p></p></body></html>';
        $subject = CssInliner::fromHtml($html);
        $subject->setDebug(false);

        $subject->inlineCss();

        $result = $subject->renderBodyContent();
        self::assertContains('color: red', $result);
        self::assertContains('background-color: blue', $result);
    }

    /**
     * @test
     */
    public function inlineCssByDefaultIgnoresInvalidCssSelectors(): void
    {
        $html = '<html><style type="text/css">' .
            'p{color:red;} <style data-x="1">html{cursor:text;} p{background-color:blue;}</style> ' .
            '<body><p></p></body></html>';
        $subject = CssInliner::fromHtml($html);

        $subject->inlineCss();

        $result = $subject->renderBodyContent();
        self::assertContains('color: red', $result);
        self::assertContains('background-color: blue', $result);
    }

    /**
     * Data provider for things that should be left out when applying the CSS.
     *
     * @return string[][]
     */
    public function unneededCssThingsDataProvider(): array
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
    public function inlineCssFiltersUnneededCssThings(string $unneededCss, string $markerNotExpectedInHtml): void
    {
        $subject = $this->buildDebugSubject('<html><p>foo</p></html>');

        $subject->inlineCss($unneededCss);

        self::assertNotContains($markerNotExpectedInHtml, $subject->render());
    }

    /**
     * @test
     *
     * @param string $unneededCss
     *
     * @dataProvider unneededCssThingsDataProvider
     */
    public function inlineCssMatchesRuleAfterUnneededCssThing(string $unneededCss): void
    {
        $subject = $this->buildDebugSubject('<html><body></body></html>');

        $subject->inlineCss($unneededCss . ' body { color: green; }');

        self::assertContains('<body style="color: green;">', $subject->render());
    }

    /**
     * Data provider for media rules.
     *
     * @return string[][]
     */
    public function mediaRulesDataProvider(): array
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
    public function inlineCssKeepsMediaRules(string $css): void
    {
        $subject = $this->buildDebugSubject('<html><p>foo</p></html>');

        $subject->inlineCss($css);

        self::assertContainsCss($css, $subject->render());
    }

    /**
     * @return string[][]
     */
    public function orderedRulesAndSurroundingCssDataProvider(): array
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
                    if (
                        ($cssBetween === '' && $cssAfter === '')
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
    public function inlineCssKeepsRulesCopiedToStyleElementInSpecifiedOrder(
        string $rule1,
        string $rule2,
        string $cssBefore,
        string $cssBetween,
        string $cssAfter
    ): void {
        $subject = $this->buildDebugSubject('<html><p><a>foo</a></p></html>');

        $subject->inlineCss($cssBefore . $rule1 . $cssBetween . $rule2 . $cssAfter);

        self::assertContainsCss($rule1 . $rule2, $subject->render());
    }

    /**
     * @test
     */
    public function removeAllowedMediaTypeProvidesFluentInterface(): void
    {
        $subject = CssInliner::fromHtml('<html></html>');

        $result = $subject->removeAllowedMediaType('screen');

        self::assertSame($subject, $result);
    }

    /**
     * @test
     */
    public function removeAllowedMediaTypeRemovesStylesForTheGivenMediaType(): void
    {
        $css = '@media screen { html { some-property: value; } }';
        $subject = $this->buildDebugSubject('<html></html>');

        $subject->removeAllowedMediaType('screen');

        $subject->inlineCss($css);
        self::assertNotContains('@media', $subject->render());
    }

    /**
     * @test
     */
    public function addAllowedMediaTypeProvidesFluentInterface(): void
    {
        $subject = CssInliner::fromHtml('<html></html>');

        $result = $subject->addAllowedMediaType('braille');

        self::assertSame($subject, $result);
    }

    /**
     * @test
     */
    public function addAllowedMediaTypeKeepsStylesForTheGivenMediaType(): void
    {
        $css = '@media braille { html { some-property: value; } }';
        $subject = $this->buildDebugSubject('<html></html>');

        $subject->addAllowedMediaType('braille');

        $subject->inlineCss($css);
        self::assertContainsCss($css, $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssKeepsExistingHeadElementContent(): void
    {
        $subject = $this->buildDebugSubject('<html><head><!-- original content --></head></html>');

        $subject->inlineCss('@media all { html { some-property: value; } }');

        self::assertContains('<!-- original content -->', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssKeepsExistingStyleElementWithMedia(): void
    {
        $html = '<!DOCTYPE html><html><head><!-- original content --></head><body></body></html>';
        $subject = $this->buildDebugSubject($html);

        $subject->inlineCss('@media all { html { some-property: value; } }');

        self::assertContains('<style type="text/css">', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssKeepsExistingStyleElementWithMediaInHead(): void
    {
        $style = '<style type="text/css">@media all { html {  color: red; } }</style>';
        $html = '<html><head>' . $style . '</head><body></body></html>';
        $subject = $this->buildDebugSubject($html);

        $subject->inlineCss();

        self::assertRegExp('/<head>.*<style.*<\\/head>/s', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssKeepsExistingStyleElementWithMediaOutOfBody(): void
    {
        $style = '<style type="text/css">@media all { html {  color: red; } }</style>';
        $html = '<html><head>' . $style . '</head><body></body></html>';
        $subject = $this->buildDebugSubject($html);

        $subject->inlineCss();

        self::assertNotRegExp('/<body>.*<style/s', $subject->render());
    }

    /**
     * Valid media query which need to be preserved
     *
     * @return string[][]
     */
    public function validMediaPreserveDataProvider(): array
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
    public function inlineCssWithValidMediaQueryContainsInnerCss(string $css): void
    {
        $subject = $this->buildDebugSubject('<html><h1></h1><p></p></html>');

        $subject->inlineCss($css);

        self::assertContainsCss('<style type="text/css">' . $css . '</style>', $subject->render());
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider validMediaPreserveDataProvider
     */
    public function inlineCssWithValidMinifiedMediaQueryContainsInnerCss(string $css): void
    {
        // Minify CSS by removing unnecessary whitespace.
        $css = \preg_replace('/\\s*{\\s*/', '{', $css);
        $css = \preg_replace('/;?\\s*}\\s*/', '}', $css);
        $css = \preg_replace('/@media{/', '@media {', $css);
        $subject = $this->buildDebugSubject('<html><h1></h1><p></p></html>');

        $subject->inlineCss($css);

        self::assertContains('<style type="text/css">' . $css . '</style>', $subject->render());
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider validMediaPreserveDataProvider
     */
    public function inlineCssForHtmlWithValidMediaQueryContainsInnerCss(string $css): void
    {
        $subject = $this->buildDebugSubject('<html><style type="text/css">' . $css . '</style><h1></h1><p></p></html>');

        $subject->inlineCss();

        self::assertContainsCss('<style type="text/css">' . $css . '</style>', $subject->render());
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider validMediaPreserveDataProvider
     */
    public function inlineCssWithValidMediaQueryNotContainsInlineCss(string $css): void
    {
        $subject = $this->buildDebugSubject('<html><h1></h1></html>');

        $subject->inlineCss($css);

        self::assertNotContains('style=', $subject->renderBodyContent());
    }

    /**
     * Invalid media query which need to be strip
     *
     * @return string[][]
     */
    public function invalidMediaPreserveDataProvider(): array
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
    public function inlineCssWithInvalidMediaQueryNotContainsInnerCss(string $css): void
    {
        $subject = $this->buildDebugSubject('<html><h1></h1></html>');

        $subject->inlineCss($css);

        self::assertNotContainsCss($css, $subject->renderBodyContent());
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider invalidMediaPreserveDataProvider
     */
    public function inlineCssWithInvalidMediaQueryNotContainsInlineCss(string $css): void
    {
        $subject = $this->buildDebugSubject('<html><h1></h1></html>');

        $subject->inlineCss($css);

        self::assertNotContains('style=', $subject->renderBodyContent());
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider invalidMediaPreserveDataProvider
     */
    public function inlineCssFromHtmlWithInvalidMediaQueryNotContainsInnerCss(string $css): void
    {
        $subject = $this->buildDebugSubject('<html><style type="text/css">' . $css . '</style><h1></h1></html>');

        $subject->inlineCss();

        self::assertNotContainsCss($css, $subject->renderBodyContent());
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider invalidMediaPreserveDataProvider
     */
    public function inlineCssFromHtmlWithInvalidMediaQueryNotContainsInlineCss(string $css): void
    {
        $subject = $this->buildDebugSubject('<html><style type="text/css">' . $css . '</style><h1></h1></html>');

        $subject->inlineCss();

        self::assertNotContains('style=', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssIgnoresEmptyMediaQuery(): void
    {
        $subject = $this->buildDebugSubject('<html><h1></h1></html>');

        $subject->inlineCss('@media screen {} @media tv { h1 { color: red; } }');

        $result = $subject->render();
        self::assertNotContains('style=', $result);
        self::assertNotContains('@media screen', $result);
    }

    /**
     * @test
     */
    public function inlineCssIgnoresMediaQueryWithWhitespaceOnly(): void
    {
        $subject = $this->buildDebugSubject('<html><h1></h1></html>');

        $subject->inlineCss('@media screen { } @media tv { h1 { color: red; } }');

        $result = $subject->render();
        self::assertNotContains('style=', $result);
        self::assertNotContains('@media screen', $result);
    }

    /**
     * @return string[][]
     */
    public function mediaTypeDataProvider(): array
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
    public function inlineCssKeepsMediaRuleAfterEmptyMediaRule(string $emptyRuleMediaType): void
    {
        $subject = $this->buildDebugSubject('<html><h1></h1></html>');

        $subject->inlineCss('@media ' . $emptyRuleMediaType . ' {} @media all { h1 { color: red; } }');

        self::assertContainsCss('@media all { h1 { color: red; } }', $subject->render());
    }

    /**
     * @test
     *
     * @param string $emptyRuleMediaType
     *
     * @dataProvider mediaTypeDataProvider
     */
    public function inlineCssNotKeepsUnneededMediaRuleAfterEmptyMediaRule(string $emptyRuleMediaType): void
    {
        $subject = $this->buildDebugSubject('<html><h1></h1></html>');

        $subject->inlineCss('@media ' . $emptyRuleMediaType . ' {} @media speech { h1 { color: red; } }');

        self::assertNotContains('@media', $subject->render());
    }

    /**
     * @param string[] $precedingSelectorComponents Array of selectors to which each type of pseudo-component is
     *                                              appended to create a selector for a CSS rule.
     *                                              Keys are human-readable descriptions.
     *
     * @psalm-param array<string, string> $precedingSelectorComponents
     *
     * @return string[][]
     *
     * @psalm-return array<string, array<int, string>>
     */
    private function getCssRuleDatasetsWithSelectorPseudoComponents(array $precedingSelectorComponents): array
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
    public function matchingSelectorWithPseudoComponentCssRuleDataProvider(): array
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
            ':optional' => ['input:optional { color: green; }'],
            ':required' => ['input:required { color: green; }'],
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
    public function inlineCssKeepsRuleWithPseudoComponentInMatchingSelector(string $css): void
    {
        $subject = $this->buildDebugSubject(
            '<html><p><a id="a" class="a" href="a">foo</a><input type="text" name="test"/></p></html>'
        );

        $subject->inlineCss($css);

        self::assertContainsCss($css, $subject->render());
    }

    /**
     * @return string[][]
     */
    public function nonMatchingSelectorWithPseudoComponentCssRuleDataProvider(): array
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
    public function inlineCssNotKeepsRuleWithPseudoComponentInNonMatchingSelector(string $css): void
    {
        $subject = $this->buildDebugSubject('<html><p><a id="a" class="a" href="#">foo</a></p></html>');

        $subject->inlineCss($css);

        self::assertNotContainsCss($css, $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssKeepsRuleInMediaQueryWithPseudoComponentInMatchingSelector(): void
    {
        $subject = $this->buildDebugSubject('<html><a>foo</a></html>');
        $css = '@media screen { a:hover { color: green; } }';

        $subject->inlineCss($css);

        self::assertContainsCss($css, $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssNotKeepsRuleInMediaQueryWithPseudoComponentInNonMatchingSelector(): void
    {
        $subject = $this->buildDebugSubject('<html><a>foo</a></html>');
        $css = '@media screen { b:hover { color: green; } }';

        $subject->inlineCss($css);

        self::assertNotContainsCss($css, $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssKeepsRuleWithPseudoComponentInMultipleMatchingSelectorsFromSingleRule(): void
    {
        $subject = $this->buildDebugSubject('<html><p>foo</p><a>bar</a></html>');
        $css = 'p:hover, a:hover { color: green; }';

        $subject->inlineCss($css);

        self::assertContainsCss($css, $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssKeepsOnlyMatchingSelectorsWithPseudoComponentFromSingleRule(): void
    {
        $subject = $this->buildDebugSubject('<html><a>foo</a></html>');

        $subject->inlineCss('p:hover, a:hover { color: green; }');

        self::assertContainsCss('<style type="text/css">a:hover { color: green; }</style>', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssAppliesCssToMatchingElementsAndKeepsRuleWithPseudoComponentFromSingleRule(): void
    {
        $subject = $this->buildDebugSubject('<html><p>foo</p><a>bar</a></html>');

        $subject->inlineCss('p, a:hover { color: green; }');

        $result = $subject->render();
        self::assertContains('<p style="color: green;">', $result);
        self::assertContainsCss('<style type="text/css">a:hover { color: green; }</style>', $result);
    }

    /**
     * @test
     */
    public function inlineCssKeepsRuleWithPseudoComponentInMatchingSelectorForHtmlWithHeader(): void
    {
        $subject = $this->buildDebugSubject('<html><header><a>foo</a></header></html>');

        $subject->inlineCss('a:hover { color: green; }');

        self::assertContainsCss('<style type="text/css">a:hover { color: green; }</style>', $subject->render());
    }

    /**
     * @return string[][]
     */
    public function mediaTypesDataProvider(): array
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
    public function inlineCssAppliesCssBetweenEmptyMediaRuleAndMediaRule(
        string $emptyRuleMediaType,
        string $mediaType
    ): void {
        $subject = $this->buildDebugSubject('<html><h1></h1></html>');

        $subject->inlineCss(
            '@media ' . $emptyRuleMediaType . ' {} h1 { color: green; } @media ' . $mediaType
            . ' { h1 { color: red; } }'
        );

        self::assertContains('<h1 style="color: green;">', $subject->render());
    }

    /**
     * @test
     *
     * @param string $emptyRuleMediaType
     * @param string $mediaType
     *
     * @dataProvider mediaTypesDataProvider
     */
    public function inlineCssAppliesCssBetweenEmptyMediaRuleAndMediaRuleWithCssAfter(
        string $emptyRuleMediaType,
        string $mediaType
    ): void {
        $subject = $this->buildDebugSubject('<html><h1></h1></html>');

        $subject->inlineCss(
            '@media ' . $emptyRuleMediaType . ' {} h1 { color: green; } @media ' . $mediaType
            . ' { h1 { color: red; } } h1 { font-size: 24px; }'
        );

        self::assertContains('<h1 style="color: green; font-size: 24px;">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssAppliesCssFromStyleNodes(): void
    {
        $styleAttributeValue = 'color: #ccc;';
        $subject = $this->buildDebugSubject(
            '<html><style type="text/css">html {' . $styleAttributeValue . '}</style></html>'
        );

        $subject->inlineCss();

        self::assertContains('<html style="' . $styleAttributeValue . '">', $subject->render());
    }

    /**
     * @test
     */
    public function disableStyleBlocksParsingProvidesFluentInterface(): void
    {
        $subject = CssInliner::fromHtml('<html></html>');

        $result = $subject->disableStyleBlocksParsing();

        self::assertSame($subject, $result);
    }

    /**
     * @test
     */
    public function inlineCssWhenDisabledNotAppliesCssFromStyleBlocks(): void
    {
        $styleAttributeValue = 'color: #ccc;';
        $subject = $this->buildDebugSubject(
            '<html><style type="text/css">html {' . $styleAttributeValue . '}</style></html>'
        );
        $subject->disableStyleBlocksParsing();

        $subject->inlineCss();

        self::assertNotContains('style=', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssWhenStyleBlocksParsingDisabledKeepInlineStyles(): void
    {
        $styleAttributeValue = 'text-align: center;';
        $subject = $this->buildDebugSubject(
            '<html><head><style type="text/css">p { color: #ccc; }</style></head>' .
            '<body><p style="' . $styleAttributeValue . '">paragraph</p></body></html>'
        );
        $subject->disableStyleBlocksParsing();

        $subject->inlineCss();

        self::assertContains('<p style="' . $styleAttributeValue . '">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function disableInlineStyleAttributesParsingProvidesFluentInterface(): void
    {
        $subject = CssInliner::fromHtml('<html></html>');

        $result = $subject->disableInlineStyleAttributesParsing();

        self::assertSame($subject, $result);
    }

    /**
     * @test
     */
    public function inlineCssWhenDisabledNotAppliesCssFromInlineStyles(): void
    {
        $subject = $this->buildDebugSubject('<html style="color: #ccc;"></html>');
        $subject->disableInlineStyleAttributesParsing();

        $subject->inlineCss();

        self::assertNotContains('<html style', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssWhenInlineStyleAttributesParsingDisabledKeepStyleBlockStyles(): void
    {
        $styleAttributeValue = 'color: #ccc;';
        $subject = $this->buildDebugSubject(
            '<html><head><style type="text/css">p { ' . $styleAttributeValue . ' }</style></head>' .
            '<body><p style="text-align: center;">paragraph</p></body></html>'
        );
        $subject->disableInlineStyleAttributesParsing();

        $subject->inlineCss();

        self::assertContains('<p style="' . $styleAttributeValue . '">', $subject->renderBodyContent());
    }

    /**
     * inlineCss was handling case differently for passed-in CSS vs. CSS parsed from style blocks.
     *
     * @test
     */
    public function inlineCssAppliesCssWithMixedCaseAttributesInStyleBlock(): void
    {
        $subject = $this->buildDebugSubject(
            '<html><head><style>#topWrap p {padding-bottom: 1px;PADDING-TOP: 0;}</style></head>' .
            '<body><div id="topWrap"><p style="text-align: center;">some content</p></div></body></html>'
        );

        $subject->inlineCss();

        $result = $subject->renderBodyContent();
        self::assertContains('<p style="padding-bottom: 1px; padding-top: 0; text-align: center;">', $result);
    }

    /**
     * Style block CSS overrides values.
     *
     * @test
     */
    public function inlineCssMergesCssWithMixedCaseAttribute(): void
    {
        $subject = $this->buildDebugSubject(
            '<html><head><style>#topWrap p {padding-bottom: 3px;PADDING-TOP: 1px;}</style></head>' .
            '<body><div id="topWrap"><p style="text-align: center;">some content</p></div></body></html>'
        );

        $subject->inlineCss('p { margin: 0; padding-TOP: 0; PADDING-bottom: 1PX;}');

        self::assertContains(
            '<p style="margin: 0; padding-bottom: 3px; padding-top: 1px; text-align: center;">',
            $subject->renderBodyContent()
        );
    }

    /**
     * @test
     */
    public function inlineCssMergesCssWithMixedUnits(): void
    {
        $subject = $this->buildDebugSubject(
            '<html><head><style>#topWrap p {margin:0;padding-bottom: 1px;}</style></head>' .
            '<body><div id="topWrap"><p style="text-align: center;">some content</p></div></body></html>'
        );

        $subject->inlineCss('p { margin: 1px; padding-bottom:0;}');

        $result = $subject->renderBodyContent();
        self::assertContains('<p style="margin: 0; padding-bottom: 1px; text-align: center;">', $result);
    }

    /**
     * @test
     */
    public function inlineCssKeepsCssMediaQueriesWithCssCommentAfterMediaQuery(): void
    {
        $subject = $this->buildDebugSubject('<html><body></body></html>');

        $subject->inlineCss('@media only screen and (max-width: 480px) { body { color: #ffffff } /* some comment */ }');

        self::assertContains('@media only screen and (max-width: 480px)', $subject->render());
    }

    /**
     * Sets HTML of subject to boilerplate HTML with a single `<p>` in `<body>` and empty `<head>`
     *
     * @param string $style Optional value for the style attribute of the `<p>` element
     *
     * @return CssInliner
     */
    private function buildSubjectWithBoilerplateHtml(string $style = ''): CssInliner
    {
        $html = '<html><head></head><body><p';
        if ($style !== '') {
            $html .= ' style="' . $style . '"';
        }
        $html .= '>some content</p></body></html>';

        return $this->buildDebugSubject($html);
    }

    /**
     * @test
     */
    public function importantInExternalCssOverwritesInlineCss(): void
    {
        $subject = $this->buildSubjectWithBoilerplateHtml('margin: 2px;');

        $subject->inlineCss('p { margin: 1px !important; }');

        self::assertContains('<p style="margin: 1px;">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function importantInExternalCssKeepsInlineCssForOtherAttributes(): void
    {
        $subject = $this->buildSubjectWithBoilerplateHtml('margin: 2px; text-align: center;');

        $subject->inlineCss('p { margin: 1px !important; }');

        self::assertContains('<p style="text-align: center; margin: 1px;">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function importantIsCaseInsensitive(): void
    {
        $subject = $this->buildSubjectWithBoilerplateHtml('margin: 2px;');

        $subject->inlineCss('p { margin: 1px !ImPorTant; }');

        self::assertContains('<p style="margin: 1px !ImPorTant;">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function secondImportantStyleOverwritesFirstOne(): void
    {
        $subject = $this->buildSubjectWithBoilerplateHtml();

        $subject->inlineCss('p { margin: 1px !important; } p { margin: 2px !important; }');

        self::assertContains('<p style="margin: 2px;">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function secondNonImportantStyleOverwritesFirstOne(): void
    {
        $subject = $this->buildSubjectWithBoilerplateHtml();

        $subject->inlineCss('p { margin: 1px; } p { margin: 2px; }');

        self::assertContains('<p style="margin: 2px;">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function secondNonImportantStyleNotOverwritesFirstImportantOne(): void
    {
        $subject = $this->buildSubjectWithBoilerplateHtml();

        $subject->inlineCss('p { margin: 1px !important; } p { margin: 2px; }');

        self::assertContains('<p style="margin: 1px;">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssAppliesLaterShorthandStyleAfterIndividualStyle(): void
    {
        $subject = $this->buildSubjectWithBoilerplateHtml();

        $subject->inlineCss('p { margin-top: 1px; } p { margin: 2px; }');

        self::assertContains('<p style="margin-top: 1px; margin: 2px;">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssAppliesLaterOverridingStyleAfterStyleAfterOverriddenStyle(): void
    {
        $subject = $this->buildSubjectWithBoilerplateHtml();

        $subject->inlineCss('p { margin-top: 1px; } p { margin: 2px; } p { margin-top: 3px; }');

        self::assertContains('<p style="margin: 2px; margin-top: 3px;">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssAppliesInlineOverridingStyleAfterCssStyleAfterOverriddenCssStyle(): void
    {
        $subject = $this->buildSubjectWithBoilerplateHtml('margin-top: 3px;');

        $subject->inlineCss('p { margin-top: 1px; } p { margin: 2px; }');

        self::assertContains('<p style="margin: 2px; margin-top: 3px;">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssAppliesLaterInlineOverridingStyleAfterEarlierInlineStyle(): void
    {
        $subject = $this->buildSubjectWithBoilerplateHtml('margin: 2px; margin-top: 3px;');

        $subject->inlineCss('p { margin-top: 1px; }');

        self::assertContains('<p style="margin: 2px; margin-top: 3px;">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function irrelevantMediaQueriesAreRemoved(): void
    {
        $subject = $this->buildDebugSubject('<html><body><p></p></body></html>');
        $uselessQuery = '@media all and (max-width: 500px) { em { color:red; } }';

        $subject->inlineCss($uselessQuery);

        self::assertNotContains('@media', $subject->render());
    }

    /**
     * @test
     */
    public function relevantMediaQueriesAreRetained(): void
    {
        $subject = $this->buildDebugSubject('<html><body><p></p></body></html>');
        $usefulQuery = '@media all and (max-width: 500px) { p { color:red; } }';

        $subject->inlineCss($usefulQuery);

        self::assertContainsCss($usefulQuery, $subject->render());
    }

    /**
     * @test
     */
    public function importantStyleRuleFromInlineCssOverwritesImportantStyleRuleFromExternalCss(): void
    {
        $subject = $this->buildSubjectWithBoilerplateHtml('margin: 2px !important; text-align: center;');

        $subject->inlineCss('p { margin: 1px !important; padding: 1px;}');

        $result = $subject->renderBodyContent();
        self::assertContains('<p style="padding: 1px; text-align: center; margin: 2px;">', $result);
    }

    /**
     * @test
     */
    public function addExcludedSelectorProvidesFluentInterface(): void
    {
        $subject = CssInliner::fromHtml('<html></html>');

        $result = $subject->addExcludedSelector('p.x');

        self::assertSame($subject, $result);
    }

    /**
     * @test
     */
    public function addExcludedSelectorIgnoresMatchingElementsFrom(): void
    {
        $subject = $this->buildDebugSubject('<html><body><p class="x"></p></body></html>');

        $subject->addExcludedSelector('p.x');
        $subject->inlineCss('p { margin: 0; }');

        self::assertContains('<p class="x"></p>', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function addExcludedSelectorExcludesMatchingElementEventWithWhitespaceAroundSelector(): void
    {
        $subject = $this->buildDebugSubject('<html><body><p class="x"></p></body></html>');

        $subject->addExcludedSelector(' p.x ');
        $subject->inlineCss('p { margin: 0; }');

        self::assertContains('<p class="x"></p>', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function addExcludedSelectorKeepsNonMatchingElements(): void
    {
        $subject = $this->buildDebugSubject('<html><body><p></p></body></html>');

        $subject->addExcludedSelector('p.x');
        $subject->inlineCss('p { margin: 0; }');

        self::assertContains('<p style="margin: 0;"></p>', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function addExcludedSelectorCanExcludeSubtree(): void
    {
        $htmlSubtree = '<div class="message-preview"><p><em>Message</em> <strong>preview.</strong></p></div>';
        $subject = $this->buildDebugSubject('<html><body>' . $htmlSubtree . '<p>Another paragraph.</p></body></html>');

        $subject->addExcludedSelector('.message-preview');
        $subject->addExcludedSelector('.message-preview *');
        $subject->inlineCss('p { margin: 0; } em { font-style: italic; } strong { font-weight: bold; }');

        self::assertContains($htmlSubtree, $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function removeExcludedSelectorProvidesFluentInterface(): void
    {
        $subject = CssInliner::fromHtml('<html></html>');

        $result = $subject->removeExcludedSelector('p.x');

        self::assertSame($subject, $result);
    }

    /**
     * @test
     */
    public function removeExcludedSelectorGetsMatchingElementsToBeInlinedAgain(): void
    {
        $subject = $this->buildDebugSubject('<html><body><p class="x"></p></body></html>');
        $subject->addExcludedSelector('p.x');

        $subject->removeExcludedSelector('p.x');
        $subject->inlineCss('p { margin: 0; }');

        self::assertContains('<p class="x" style="margin: 0;"></p>', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssInDebugModeForInvalidExcludedSelectorThrowsException(): void
    {
        $this->expectException(SyntaxErrorException::class);

        $subject = CssInliner::fromHtml('<html></html>');
        $subject->setDebug(true);

        $subject->addExcludedSelector('..p');
        $subject->inlineCss();
    }

    /**
     * @test
     */
    public function inlineCssNotInDebugModeIgnoresInvalidExcludedSelector(): void
    {
        $subject = CssInliner::fromHtml('<html><p class="x"></p></html>');
        $subject->setDebug(false);

        $subject->addExcludedSelector('..p');
        $subject->inlineCss();

        self::assertContains('<p class="x"></p>', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssNotInDebugModeIgnoresOnlyInvalidExcludedSelector(): void
    {
        $subject = CssInliner::fromHtml('<html><p class="x"></p><p class="y"></p><p class="z"></p></html>');
        $subject->setDebug(false);

        $subject->addExcludedSelector('p.x');
        $subject->addExcludedSelector('..p');
        $subject->addExcludedSelector('p.z');
        $subject->inlineCss('p { color: red };');

        $result = $subject->renderBodyContent();
        self::assertContains('<p class="x"></p>', $result);
        self::assertContains('<p class="y" style="color: red;"></p>', $result);
        self::assertContains('<p class="z"></p>', $result);
    }

    /**
     * @test
     */
    public function emptyMediaQueriesAreRemoved(): void
    {
        $subject = $this->buildDebugSubject('<html><body><p></p></body></html>');
        $emptyQuery = '@media all and (max-width: 500px) { }';

        $subject->inlineCss($emptyQuery);

        self::assertNotContains('@media', $subject->render());
    }

    /**
     * @test
     */
    public function multiLineMediaQueryWithWindowsLineEndingsIsAppliedOnlyOnce(): void
    {
        $subject = $this->buildDebugSubject(
            '<html><body>' .
            '<p class="medium">medium</p>' .
            '<p class="small">small</p>' .
            '</body></html>'
        );
        $css = "@media all {\r\n" .
            ".medium {font-size:18px;}\r\n" .
            ".small {font-size:14px;}\r\n" .
            '}';

        $subject->inlineCss($css);

        self::assertContainsCssCount(1, $css, $subject->render());
    }

    /**
     * @test
     */
    public function multiLineMediaQueryWithUnixLineEndingsIsAppliedOnlyOnce(): void
    {
        $subject = $this->buildDebugSubject(
            '<html><body>' .
            '<p class="medium">medium</p>' .
            '<p class="small">small</p>' .
            '</body></html>'
        );
        $css = "@media all {\n" .
            ".medium {font-size:18px;}\n" .
            ".small {font-size:14px;}\n" .
            '}';

        $subject->inlineCss($css);

        self::assertContainsCssCount(1, $css, $subject->render());
    }

    /**
     * @test
     */
    public function multipleMediaQueriesAreAppliedOnlyOnce(): void
    {
        $subject = $this->buildDebugSubject(
            '<html><body>' .
            '<p class="medium">medium</p>' .
            '<p class="small">small</p>' .
            '</body></html>'
        );
        $css = "@media all {\n" .
            ".medium {font-size:18px;}\n" .
            ".small {font-size:14px;}\n" .
            '}' .
            "@media screen {\n" .
            ".medium {font-size:24px;}\n" .
            ".small {font-size:18px;}\n" .
            '}';

        $subject->inlineCss($css);

        self::assertContainsCssCount(1, $css, $subject->render());
    }

    /**
     * @return string[][]
     */
    public function dataUriMediaTypeDataProvider(): array
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
    public function dataUrisAreConserved(string $dataUriMediaType): void
    {
        $subject = $this->buildDebugSubject('<html></html>');
        $styleRule = 'background-image: url(data:image/png' . $dataUriMediaType .
            ',iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAIAAAAC64paAAABUk' .
            'lEQVQ4y81UsY6CQBCdWXBjYWFMjEgAE0piY8c38B9+iX+ksaHCgs5YWEhIrJCQYGJBomiC7lzhVcfqEa+5KXfey3s783bRdd00TR' .
            'VFAQAAICJEhN/q8Xjoug7D4RA+qsFgwDjn9QYiTiaT+Xx+OByOx+NqtapjWq0WjEajekPTtCAIiIiIyrKMoqiOMQxDlVqyLMt1XQ' .
            'A4nU6z2Wy9XkthEnK/3zdN8znC/X7v+36WZfJ7120vFos4joUQRHS5XDabzXK5bGrbtu1er/dtTFU1TWu3202VHceZTqe3242Itt' .
            'ut53nj8bip8m6345wLIQCgKIowDIuikAoz6Wm3233mjHPe6XRe5UROJqImIWPwh/pvZMbYM2GKorx5oUw6m+v1miTJ+XzO8/x+v7' .
            '+UtizrM8+GYahVVSFik9/jxy6rqlJN02SM1cmI+GbbQghd178AAO2FXws6LwMAAAAASUVORK5CYII=);';

        $subject->inlineCss('html {' . $styleRule . '}');

        self::assertContains('<html style="' . $styleRule . '">', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssIgnoresPseudoClassCombinedWithPseudoElement(): void
    {
        $subject = $this->buildDebugSubject('<html><body><div></div></body></html>');

        $subject->inlineCss('div:last-child::after {float: right;}');

        self::assertContains('<div></div>', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssKeepsInlineStylePriorityVersusStyleBlockRules(): void
    {
        $subject = $this->buildDebugSubject(
            '<html><head><style>p {padding:10px};</style></head><body><p style="padding-left:20px;"></p></body></html>'
        );

        $subject->inlineCss();

        self::assertContains('<p style="padding: 10px; padding-left: 20px;">', $subject->renderBodyContent());
    }

    /**
     * @return string[][]
     */
    public function cssForImportantRuleRemovalDataProvider(): array
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
    public function inlineCssRemovesImportantRule(
        string $originalStyleAttributeContent,
        string $expectedStyleAttributeContent
    ): void {
        $subject = $this->buildDebugSubject(
            '<html><head><body><p style="' . $originalStyleAttributeContent . '"></p></body></html>'
        );

        $subject->inlineCss();

        self::assertContains('<p style="' . $expectedStyleAttributeContent . '">', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function inlineCssInDebugModeForInvalidSelectorsInMediaQueryBlocksThrowsException(): void
    {
        $this->expectException(SyntaxErrorException::class);

        $subject = CssInliner::fromHtml('<html></html>');
        $subject->setDebug(true);

        $subject->inlineCss('@media screen {p^^ {color: red;}}');
    }

    /**
     * @test
     */
    public function inlineCssNotInDebugModeKeepsInvalidOrUnrecognizedSelectorsInMediaQueryBlocks(): void
    {
        $subject = CssInliner::fromHtml('<html></html>');
        $subject->setDebug(false);
        $css = '@media screen {p^^ {color: red;}}';

        $subject->inlineCss($css);

        self::assertContainsCss($css, $subject->render());
    }

    /**
     * @return string[][]
     */
    public function provideCssRulesWithUnsupportedSelectorCombination(): array
    {
        return [
            ':first-of-type without type' => [':first-of-type { color: red; }'],
            ':last-of-type without type' => [':last-of-type { color: red; }'],
            ':nth-last-of-type without type' => [':nth-last-of-type(2n) { color: red; }'],
        ];
    }

    /**
     * This test enstablishes the current expected/observed behaviour with currently supported versions of
     * `symfony/css-selector` for static pseudo-classes which are only partially supported.
     *
     * The handling of these selectors should be revisited - rules with unsupported combinations should be copied to a
     * <style> element so that they can at least be applied correctly by fully-supporting email clients.  It is also
     * possible that (before then) future changes to Symfony may break this test, in which case the documentation would
     * need updating and the tests adjusting.
     *
     * @test
     *
     * @param string $css
     *
     * @dataProvider provideCssRulesWithUnsupportedSelectorCombination
     */
    public function inlineCssNotInDebugModeDiscardsRulesWithUnsupportedSelectorCombination(string $css): void
    {
        $subject = CssInliner::fromHtml('<html><p>Hello</p><p>World</p></html>');
        $subject->setDebug(false);

        $subject->inlineCss($css);

        $result = $subject->render();
        self::assertNotContainsCss($css, $result);
        self::assertNotContains('color: red', $result);
    }

    /**
     * @return string[][]
     */
    public function provideCssRulesWithPossiblyIncorrectlyImplementedSelectorCombination(): array
    {
        return [
            ':only-of-type without type' => [':only-of-type { color: red; }'],
        ];
    }

    /**
     * This test enstablishes the current expected/observed behaviour with currently supported versions of
     * `symfony/css-selector` for static pseudo-classes which are only partially supported.
     *
     * The handling of these selectors should be revisited - rules with unsupported combinations should be copied to a
     * <style> element so that they can at least be applied correctly by fully-supporting email clients.  It is also
     * possible that (before then) future changes to Symfony may break this test, in which case the documentation would
     * need updating and the tests adjusting.
     *
     * @test
     *
     * @param string $css
     *
     * @dataProvider provideCssRulesWithPossiblyIncorrectlyImplementedSelectorCombination
     */
    public function inlineCssNotInDebugModeMayDiscardRulesWithPossiblyIncorrectlyImplementedSelectorCombination(
        string $css
    ): void {
        $subject = CssInliner::fromHtml('<html><p>Hello</p><p>World</p></html>');
        $subject->setDebug(false);

        $subject->inlineCss($css);

        $result = $subject->render();
        self::assertNotContainsCss($css, $result);
        // The declaration may or may not be haphazardly applied depending on `symofony/css-selector` version.
        // Nothing more can be asserted that would always be true for the full range of versions supported.
    }

    /**
     * @return string[][]
     */
    public function provideCssRulesWithIncorrectlyImplementedSelectorCombination(): array
    {
        return [
            ':nth-of-type without type' => [':nth-of-type(2n) { color: red; }'],
        ];
    }

    /**
     * This test enstablishes the current expected/observed behaviour with currently supported versions of
     * `symfony/css-selector` for static pseudo-classes which are only partially supported.
     *
     * The handling of these selectors should be revisited - rules with unsupported combinations should be copied to a
     * <style> element so that they can at least be applied correctly by fully-supporting email clients.  It is also
     * possible that (before then) future changes to Symfony may break this test, in which case the documentation would
     * need updating and the tests adjusting.
     *
     * @test
     *
     * @param string $css
     *
     * @dataProvider provideCssRulesWithIncorrectlyImplementedSelectorCombination
     */
    public function inlineCssAppliesRulesWithIncorrectlyImplementedSelectorCombination(string $css): void
    {
        $subject = $this->buildDebugSubject('<html><p>Hello</p><p>World</p></html>');

        $subject->inlineCss($css);

        $result = $subject->render();
        self::assertNotContainsCss($css, $result);
        // The declaration will currently be haphazardly applied: in the case of `...of-type`, as if `...child`.
        self::assertContains('color: red', $result);
    }

    /**
     * @return string[][]
     */
    public function provideValidImportRules(): array
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
    public function inlineCssPreservesValidImportRules(string $cssBefore, string $cssImports, string $cssAfter): void
    {
        $subject = $this->buildDebugSubject('<html><p>foo</p></html>');

        $subject->inlineCss($cssBefore . $cssImports . $cssAfter);

        self::assertContains($cssImports, $subject->render());
    }

    /**
     * @return string[][]
     */
    public function provideInvalidImportRules(): array
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
    public function inlineCssRemovesInvalidImportRules(string $css): void
    {
        $subject = $this->buildDebugSubject('<html><p>foo</p></html>');

        $subject->inlineCss($css);

        self::assertNotContains('@import', $subject->render());
    }

    /**
     * @test
     */
    public function inlineCssNotCopiesInlinableRuleAfterImportRuleToStyleElement(): void
    {
        $cssImport = '@import "foo.css";';
        $cssAfter = 'p { color: red; }';
        $subject = $this->buildDebugSubject('<html><p>foo</p></html>');

        $subject->inlineCss($cssImport . "\n" . $cssAfter);

        self::assertNotContainsCss($cssAfter, $subject->render());
    }

    /**
     * @return string[][]
     */
    public function provideValidFontFaceRules(): array
    {
        return [
            'single @font-face' => [
                'before' => '',
                '@font-face' => '@font-face { font-family: "Foo Sans"; src: url("/foo-sans.woff2") format("woff2"); }',
                'after' => '',
            ],
            'uppercase @FONT-FACE' => [
                'before' => '',
                '@font-face' => '@FONT-FACE { font-family: "Foo Sans"; src: url("/foo-sans.woff2") format("woff2"); }',
                'after' => '',
            ],
            'mixed case @FoNt-FaCe' => [
                'before' => '',
                '@font-face' => '@FoNt-FaCe { font-family: "Foo Sans"; src: url("/foo-sans.woff2") format("woff2"); }',
                'after' => '',
            ],
            '2 @font-faces' => [
                'before' => '',
                '@font-face' => '@font-face { font-family: "Foo Sans"; src: url("/foo-sans.woff2") format("woff2"); }'
                    . "\n" . '@font-face { font-family: "Bar Sans"; src: url("/bar-sans.woff2") format("woff2"); }',
                'after' => '',
            ],
            '2 @font-faces, minified' => [
                'before' => '',
                '@font-face' => '@font-face{font-family:"Foo Sans";src:url(/foo-sans.woff2) format("woff2")}'
                    . '@font-face{font-family:"Bar Sans";src:url(/bar-sans.woff2) format("woff2")}',
                'after' => '',
            ],
            '@font-face followed by matching inlinable rule' => [
                'before' => '',
                '@font-face' => '@font-face { font-family: "Foo Sans"; src: url("/foo-sans.woff2") format("woff2"); }',
                'after' => "\n" . 'p { color: green; }',
            ],
            '@font-face followed by matching uninlinable rule' => [
                'before' => '',
                '@font-face' => '@font-face { font-family: "Foo Sans"; src: url("/foo-sans.woff2") format("woff2"); }',
                'after' => "\n" . 'p:hover { color: green; }',
            ],
            '@font-face followed by matching @media rule' => [
                'before' => '',
                '@font-face' => '@font-face { font-family: "Foo Sans"; src: url("/foo-sans.woff2") format("woff2"); }',
                'after' => "\n" . '@media (max-width: 640px) { p { color: green; } }',
            ],
            '@font-face preceded by matching inlinable rule' => [
                'before' => "p { color: green; }\n",
                '@font-face' => '@font-face { font-family: "Foo Sans"; src: url("/foo-sans.woff2") format("woff2"); }',
                'after' => '',
            ],
            '@font-face preceded by matching uninlinable rule' => [
                'before' => "p:hover { color: green; }\n",
                '@font-face' => '@font-face { font-family: "Foo Sans"; src: url("/foo-sans.woff2") format("woff2"); }',
                'after' => '',
            ],
            '@font-face preceded by matching @media rule' => [
                'before' => "@media (max-width: 640px) { p { color: green; } }\n",
                '@font-face' => '@font-face { font-family: "Foo Sans"; src: url("/foo-sans.woff2") format("woff2"); }',
                'after' => '',
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $cssBefore
     * @param string $cssFontFaces
     * @param string $cssAfter
     *
     * @dataProvider provideValidFontFaceRules
     */
    public function inlineCssPreservesValidFontFaceRules(
        string $cssBefore,
        string $cssFontFaces,
        string $cssAfter
    ): void {
        $subject = $this->buildDebugSubject('<html><p>foo</p></html>');

        $subject->inlineCss($cssBefore . $cssFontFaces . $cssAfter);

        self::assertContains($cssFontFaces, $subject->render());
    }

    /**
     * @return string[][]
     */
    public function provideInvalidFontFaceRules(): array
    {
        return [
            '@font-face without font-family descriptor' => [
                '@font-face { src: url("/foo-sans.woff2") format("woff2"); }',
            ],
            '@font-face without src descriptor' => [
                '@font-face { font-family: "Foo Sans"; }',
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $css
     *
     * @dataProvider provideInvalidFontFaceRules
     */
    public function inlineCssRemovesInvalidFontFaceRules(string $css): void
    {
        $subject = $this->buildDebugSubject('<html></html>');

        $subject->inlineCss($css);

        self::assertNotContains('@font-face', $subject->render());
    }

    /**
     * @test
     */
    public function getMatchingUninlinableSelectorsThrowsExceptionIfInlineCssNotCalled(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $subject = $this->buildDebugSubject('<html></html>');

        $subject->getMatchingUninlinableSelectors();
    }

    /**
     * @return string[][][]
     */
    public function matchingUninlinableSelectorsDataProvider(): array
    {
        return [
            '1 matching uninlinable selector' => [['p:hover']],
            '2 matching uninlinable selectors' => [['p:hover', 'p::after']],
        ];
    }

    /**
     * @test
     *
     * @param string[] $selectors
     *
     * @dataProvider matchingUninlinableSelectorsDataProvider
     */
    public function getMatchingUninlinableSelectorsReturnsMatchingUninlinableSelectors(array $selectors): void
    {
        $css = \implode(' ', \array_map(
            static function ($selector) {
                return $selector . ' { color: green; }';
            },
            $selectors
        ));
        $subject = $this->buildDebugSubject('<html><p>foo</p></html>');
        $subject->inlineCss($css);

        $result = $subject->getMatchingUninlinableSelectors();

        foreach ($selectors as $selector) {
            self::assertContains($selector, $result);
        }
    }

    /**
     * @return string[][]
     */
    public function nonMatchingOrInlinableSelectorDataProvider(): array
    {
        return [
            'non matching uninlinable selector' => ['a:hover'],
            'matching inlinable selector' => ['p'],
            'non matching inlinable selector' => ['a'],
        ];
    }

    /**
     * @test
     *
     * @param string $selector
     *
     * @dataProvider nonMatchingOrInlinableSelectorDataProvider
     */
    public function getMatchingUninlinableSelectorsNotReturnsNonMatchingOrInlinableSelector(string $selector): void
    {
        $subject = $this->buildDebugSubject('<html><p>foo</p></html>');
        $subject->inlineCss($selector . ' { color: red; }');

        $result = $subject->getMatchingUninlinableSelectors();

        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function copyUninlinableCssToStyleNodeHasNoSideEffects(): void
    {
        $subject = $this->buildDebugSubject('<html><a>foo</a><p>bar</p></html>');
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
        $matchingUninlinableCssRulesProperty = new \ReflectionProperty(
            CssInliner::class,
            'matchingUninlinableCssRules'
        );
        $matchingUninlinableCssRulesProperty->setAccessible(true);
        $matchingUninlinableCssRulesProperty->setValue($subject, $uninlinableCssRules);

        $copyUninlinableCssToStyleNode = new \ReflectionMethod(CssInliner::class, 'copyUninlinableCssToStyleNode');
        $copyUninlinableCssToStyleNode->setAccessible(true);

        $domDocument = $subject->getDomDocument();

        $copyUninlinableCssToStyleNode->invoke($subject, '');
        $expectedHtml = $subject->render();

        $styleElement = $domDocument->getElementsByTagName('style')->item(0);
        self::assertInstanceOf(\DOMElement::class, $styleElement);
        $styleElement->parentNode->removeChild($styleElement);

        $copyUninlinableCssToStyleNode->invoke($subject, '');

        self::assertSame($expectedHtml, $subject->render());
    }
}
