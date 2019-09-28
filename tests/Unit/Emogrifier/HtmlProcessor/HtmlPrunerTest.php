<?php

namespace Pelago\Tests\Unit\Emogrifier\HtmlProcessor;

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <github@oliverklee.de>
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
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

    /**
     * @return string[][]
     */
    public function provideEmogrifierKeepClassAttribute()
    {
        return [
            'special class alone' => ['-emogrifier-keep'],
            'special class after another' => ['preheader -emogrifier-keep'],
            'special class before another' => ['-emogrifier-keep preheader'],
        ];
    }

    /**
     * @test
     *
     * @param string $classAttributeValue
     *
     * @dataProvider provideEmogrifierKeepClassAttribute
     */
    public function removeElementsWithDisplayNoneNotRemovesElementsWithClassEmogrifierKeep($classAttributeValue)
    {
        $subject = HtmlPruner::fromHtml(
            '<html><body><div class="' . $classAttributeValue . '" style="display: none;"></div></body></html>'
        );

        $subject->removeElementsWithDisplayNone();

        self::assertContains('<div', $subject->render());
    }

    /**
     * @test
     */
    public function removeRedundantClassesProvidesFluentInterface()
    {
        $subject = HtmlPruner::fromHtml('<html></html>');

        $result = $subject->removeRedundantClasses();

        self::assertSame($subject, $result);
    }

    /**
     * @return string[][][]
     */
    public function provideClassesToKeep()
    {
        return [
            'no classes to keep' => [[]],
            '1 class to keep' => [['foo']],
            '2 classes to keep' => [['foo', 'bar']],
        ];
    }

    /**
     * @test
     *
     * @param string[] $classesToKeep
     *
     * @dataProvider provideClassesToKeep
     */
    public function removeRedundantClassesPreservesHtmlWithoutClasses(array $classesToKeep)
    {
        $html = '<p style="color: green;">hello</p>';
        $subject = HtmlPruner::fromHtml('<html>' . $html . '</html>');

        $subject->removeRedundantClasses($classesToKeep);

        self::assertContains($html, $subject->render());
    }

    /**
     * @return (string|string[])[][]
     */
    public function provideHtmlAndNonMatchedClasses()
    {
        return [
            '1 attribute, 1 class, no classes to keep' => [
                'HTML' => '<p class="foo">hello</p>',
                'classes to keep' => [],
            ],
            '2 attributes, 1 different class each, no classes to keep' => [
                'HTML' => '<p class="foo">hello</p><p class="bar">world</p>',
                'classes to keep' => [],
            ],
            '1 attribute, 1 class, 1 different class to keep' => [
                'HTML' => '<p class="foo">hello</p>',
                'classes to keep' => ['baz'],
            ],
            '2 attributes, 1 different class each, 1 different class to keep' => [
                'HTML' => '<p class="foo">hello</p><p class="bar">world</p>',
                'classes to keep' => ['baz'],
            ],
            '2 attributes, same 1 class each, 1 different class to keep' => [
                'HTML' => '<p class="foo">hello</p><p class="foo">world</p>',
                'classes to keep' => ['baz'],
            ],
            '1 attribute, 2 classes, 1 different class to keep' => [
                'HTML' => '<p class="foo bar">hello</p>',
                'classes to keep' => ['baz'],
            ],
            '1 attribute, 1 class with extra whitespace, 1 different class to keep' => [
                'HTML' => '<p class=" foo ">hello</p>',
                'classes to keep' => ['baz'],
            ],
            '1 attribute, 2 classes with extra whitespace, 1 different class to keep' => [
                'HTML' => '<p class=" foo  bar ">hello</p>',
                'classes to keep' => ['baz'],
            ],
            '1 attribute, 2 classes separated by newline, 1 different class to keep' => [
                'HTML' => "<p class=\"foo\nbar\">hello</p>",
                'classes to keep' => ['baz'],
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     * @param string[] $classesToKeep
     *
     * @dataProvider provideHtmlAndNonMatchedClasses
     */
    public function removeRedundantClassesRemovesClassAttributesContainingNoClassesToKeep($html, array $classesToKeep)
    {
        $subject = HtmlPruner::fromHtml('<html>' . $html . '</html>');

        $subject->removeRedundantClasses($classesToKeep);

        self::assertNotContains('class', $subject->render());
    }

    /**
     * @return (string|string[])[][]
     */
    public function provideHtmlAndSomeMatchedClasses()
    {
        return [
            '2 attributes, 1 different class each, 1st class to be kept' => [
                'HTML' => '<p class="foo">hello</p><p class="bar">world</p>',
                'classes to keep' => ['foo'],
                'classes expected to be removed' => ['bar'],
            ],
            '2 attributes, 1 different class each, 2nd class to be kept' => [
                'HTML' => '<p class="foo">hello</p><p class="bar">world</p>',
                'classes to keep' => ['bar'],
                'classes expected to be removed' => ['foo'],
            ],
            'first class in attribute is to be removed' => [
                'HTML' => '<p class="foo bar baz">hello</p>',
                'classes to keep' => ['bar', 'baz'],
                'classes expected to be removed' => ['foo'],
            ],
            'middle class in attribute is to be removed' => [
                'HTML' => '<p class="foo bar baz">hello</p>',
                'classes to keep' => ['foo', 'baz'],
                'classes expected to be removed' => ['bar'],
            ],
            'last class in attribute is to be removed' => [
                'HTML' => '<p class="foo bar baz">hello</p>',
                'classes to keep' => ['foo', 'bar'],
                'classes expected to be removed' => ['baz'],
            ],
        ];
    }

    /**
     * @return (string|string[])[][]
     */
    public function provideHtmlWithExtraWhitespaceAndSomeMatchedClasses()
    {
        return [
            '1 attribute, 2 classes with extra whitespace, 1 to be kept' => [
                'HTML' => '<p class=" foo  bar ">hello</p>',
                'classes to keep' => ['foo'],
                'classes expected to be removed' => ['bar'],
            ],
            '1 attribute, 2 classes separated by newline, 1 to be kept' => [
                'HTML' => "<p class=\"foo\nbar\">hello</p>",
                'classes to keep' => ['foo'],
                'classes expected to be removed' => ['bar'],
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     * @param string[] $classesToKeep
     * @param string[] $classesExpectedToBeRemoved
     *
     * @dataProvider provideHtmlAndSomeMatchedClasses
     * @dataProvider provideHtmlWithExtraWhitespaceAndSomeMatchedClasses
     */
    public function removeRedundantClassesRemovesClassesNotToKeep(
        $html,
        array $classesToKeep,
        array $classesExpectedToBeRemoved
    ) {
        $subject = HtmlPruner::fromHtml('<html>' . $html . '</html>');

        $subject->removeRedundantClasses($classesToKeep);

        $result = $subject->render();
        self::assertContainsNone($classesExpectedToBeRemoved, $result);
    }

    /**
     * @return (string|string[])[][]
     */
    public function provideHtmlAndAllMatchedClasses()
    {
        return [
            '1 attribute, 1 class, that class to be kept' => [
                'HTML' => '<p class="foo">hello</p>',
                'classes to keep' => ['foo'],
            ],
            '2 attributes, 1 different class each, both classes to be kept' => [
                'HTML' => '<p class="foo">hello</p><p class="bar">world</p>',
                'classes to keep' => ['foo', 'bar'],
            ],
            '2 attributes, same 1 class each, that class to be kept' => [
                'HTML' => '<p class="foo">hello</p><p class="foo">world</p>',
                'classes to keep' => ['foo'],
            ],
            '1 attribute, 2 classes, both to be kept' => [
                'HTML' => '<p class="foo bar">hello</p>',
                'classes to keep' => ['foo', 'bar'],
            ],
        ];
    }

    /**
     * @return (string|string[])[][]
     */
    public function provideHtmlWithExtraWhitespaceAndAllMatchedClasses()
    {
        return [
            '1 attribute, 1 class with extra whitespace, that class to be kept' => [
                'HTML' => '<p class=" foo ">hello</p>',
                'classes to keep' => ['foo'],
            ],
            '1 attribute, 2 classes with extra whitespace, both to be kept' => [
                'HTML' => '<p class=" foo  bar ">hello</p>',
                'classes to keep' => ['foo', 'bar'],
            ],
            '1 attribute, 2 classes separated by newline, both to be kept' => [
                'HTML' => "<p class=\"foo\nbar\">hello</p>",
                'classes to keep' => ['foo', 'bar'],
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     * @param string[] $classesToKeep
     *
     * @dataProvider provideHtmlAndSomeMatchedClasses
     * @dataProvider provideHtmlAndAllMatchedClasses
     * @dataProvider provideHtmlWithExtraWhitespaceAndSomeMatchedClasses
     * @dataProvider provideHtmlWithExtraWhitespaceAndAllMatchedClasses
     */
    public function removeRedundantClassesNotRemovesClassesToKeep($html, array $classesToKeep)
    {
        $subject = HtmlPruner::fromHtml('<html>' . $html . '</html>');

        $subject->removeRedundantClasses($classesToKeep);

        $result = $subject->render();
        foreach ($classesToKeep as $class) {
            $expectedInstanceCount = \substr_count($html, $class);
            self::assertSubstringCount($expectedInstanceCount, $result, $class);
        }
    }

    /**
     * @test
     *
     * @param string $html
     * @param string[] $classesToKeep
     *
     * @dataProvider provideHtmlWithExtraWhitespaceAndSomeMatchedClasses
     * @dataProvider provideHtmlWithExtraWhitespaceAndAllMatchedClasses
     */
    public function removeRedundantClassesMinifiesClassAttributes($html, array $classesToKeep)
    {
        $subject = HtmlPruner::fromHtml('<html>' . $html . '</html>');

        $subject->removeRedundantClasses($classesToKeep);

        \preg_match_all('/class="([^"]*+)"/', $subject->render(), $classAttributeMatches);
        foreach ($classAttributeMatches[1] as $classAttributeValue) {
            self::assertMinified($classAttributeValue);
        }
    }

    /**
     * Builds a `CssInliner` fixture with the given HTML in a state where the given CSS has been inlined, and an
     * `HtmlPruner` subject sharing the same `DOMDocument`.
     *
     * @param string $html
     * @param string $css
     *
     * @return (CssInliner|HtmlPruner)[] The `HtmlPruner` subject is the first element, and the `CssInliner` fixture is
     *         the second element.
     */
    private function buildSubjectAndCssInlinerWithCssInlined($html, $css)
    {
        $cssInliner = CssInliner::fromHtml($html);
        $cssInliner->inlineCss($css);

        $subject = HtmlPruner::fromDomDocument($cssInliner->getDomDocument());

        return [$subject, $cssInliner];
    }

    /**
     * @test
     */
    public function removeRedundantClassesAfterCssInlinedProvidesFluentInterface()
    {
        list($subject, $cssInliner) = $this->buildSubjectAndCssInlinerWithCssInlined('<html></html>', '');

        $result = $subject->removeRedundantClassesAfterCssInlined($cssInliner);

        self::assertSame($subject, $result);
    }

    /**
     * @test
     */
    public function removeRedundantClassesAfterCssInlinedThrowsExceptionIfInlineCssNotCalled()
    {
        $this->expectException(\BadMethodCallException::class);

        $cssInliner = CssInliner::fromHtml('<html></html>');
        $subject = HtmlPruner::fromDomDocument($cssInliner->getDomDocument());

        $subject->removeRedundantClassesAfterCssInlined($cssInliner);
    }

    /**
     * @return (string|string[])[][]
     */
    public function provideClassesNotInUninlinableRules()
    {
        return [
            'inlinable rule with different class' => [
                'HTML' => '<p class="foo">hello</p>',
                'CSS' => '.bar { color: red; }',
                'classes expected to be removed' => ['foo'],
            ],
            'uninlinable rule with different class' => [
                'HTML' => '<p class="foo">hello</p>',
                'CSS' => '.bar:hover { color: red; }',
                'classes expected to be removed' => ['foo'],
            ],
            'inlinable rule with matching class' => [
                'HTML' => '<p class="foo">hello</p>',
                'CSS' => '.foo { color: red; }',
                'classes expected to be removed' => ['foo'],
            ],
            '2 instances of class to be removed' => [
                'HTML' => '<p class="foo">hello</p><p class="foo">world</p>',
                'CSS' => '.foo { color: red; }',
                'classes expected to be removed' => ['foo'],
            ],
            '2 different classes to be removed' => [
                'HTML' => '<p class="foo">hello</p><p class="bar">world</p>',
                'CSS' => '.foo { color: red; }',
                'classes expected to be removed' => ['foo', 'bar'],
            ],
            '2 different classes, 1 in inlinable rule, 1 in uninlinable rule' => [
                'HTML' => '<p class="foo bar">hello</p>',
                'CSS' => '.foo { color: red; } .bar:hover { color: green; }',
                'classes expected to be removed' => ['foo'],
            ],
            'class with hyphen, underscore, uppercase letter and number in name' => [
                'HTML' => '<p class="foo-2_A">hello</p>',
                'CSS' => '.foo-2_A { color: red; }',
                'classes expected to be removed' => ['foo-2_A'],
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     * @param string $css
     * @param string[] $classesExpectedToBeRemoved
     *
     * @dataProvider provideClassesNotInUninlinableRules
     */
    public function removeRedundantClassesAfterCssInlinedRemovesClassesNotInUninlinableRules(
        $html,
        $css,
        array $classesExpectedToBeRemoved = []
    ) {
        list($subject, $cssInliner)
            = $this->buildSubjectAndCssInlinerWithCssInlined('<html>' . $html . '</html>', $css);

        $subject->removeRedundantClassesAfterCssInlined($cssInliner);

        $result = $subject->render();
        self::assertContainsNone($classesExpectedToBeRemoved, $result);
    }

    /**
     * @return (string|string[])[][]
     */
    public function provideClassesInUninlinableRules()
    {
        return [
            'media rule' => [
                'HTML' => '<p class="foo">hello</p>',
                'CSS' => '@media (max-width: 640px) { .foo { color: green; } }',
                'classes to be kept' => ['foo'],
            ],
            'dynamic pseudo-class' => [
                'HTML' => '<p class="foo">hello</p>',
                'CSS' => '.foo:hover { color: green; }',
                'classes to be kept' => ['foo'],
            ],
            '2 classes, in different uninlinable rules' => [
                'HTML' => '<p class="foo bar">hello</p>',
                'CSS' => '.foo:hover { color: green; } @media (max-width: 640px) { .bar { color: green; } }',
                'classes to be kept' => ['foo', 'bar'],
            ],
            '1 class in uninlinable rule, 1 in inlinable rule' => [
                'HTML' => '<p class="foo bar">hello</p>',
                'CSS' => '.foo { color: red; } .bar:hover { color: green; }',
                'classes to be kept' => ['bar'],
            ],
            '2 classes in same selector' => [
                'HTML' => '<p class="foo bar">hello</p>',
                'CSS' => '.foo.bar:hover { color: green; }',
                'classes to be kept' => ['foo', 'bar'],
            ],
            'class with hyphen, underscore, uppercase letter and number in name' => [
                'HTML' => '<p class="foo-2_A">hello</p>',
                'CSS' => '.foo-2_A:hover { color: green; }',
                'classes to be kept' => ['foo-2_A'],
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $html
     * @param string $css
     * @param string[] $classesToBeKept
     *
     * @dataProvider provideClassesInUninlinableRules
     */
    public function removeRedundantClassesAfterCssInlinedNotRemovesClassesInUninlinableRules(
        $html,
        $css,
        array $classesToBeKept = []
    ) {
        list($subject, $cssInliner)
            = $this->buildSubjectAndCssInlinerWithCssInlined('<html>' . $html . '</html>', $css);

        $subject->removeRedundantClassesAfterCssInlined($cssInliner);

        $result = $subject->render();
        self::assertContainsAll($classesToBeKept, $result);
    }

    /**
     * Asserts that none of the `$needles` can be found within the string `$haystack`.
     *
     * @param string[] $needles
     * @param string $haystack
     *
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    private static function assertContainsNone(array $needles, $haystack)
    {
        foreach ($needles as $needle) {
            self::assertNotContains($needle, $haystack);
        }
    }

    /**
     * Asserts that all of the `$needles` can be found within the string `$haystack`.
     *
     * @param string[] $needles
     * @param string $haystack
     *
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    private static function assertContainsAll(array $needles, $haystack)
    {
        foreach ($needles as $needle) {
            self::assertContains($needle, $haystack);
        }
    }

    /**
     * Asserts that the number of occurrences of `$needle` within the string `$haystack` is as expected.
     *
     * @param int $expectedCount
     * @param string $haystack
     * @param string $needle
     *
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    private static function assertSubstringCount($expectedCount, $haystack, $needle)
    {
        self::assertSame(
            $expectedCount,
            \substr_count($haystack, $needle),
            'asserting \'' . $haystack . '\' contains ' . $expectedCount . ' instance(s) of "' . $needle . '"'
        );
    }

    /**
     * Asserts that a string does not contain consecutive whitespace characters, or begin or end with whitespace.
     *
     * @param string $string
     *
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    private static function assertMinified($string)
    {
        self::assertNotRegExp('/^\\s|\\s{2}|\\s$/', $string);
    }
}
