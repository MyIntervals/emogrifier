<?php

namespace Pelago\Tests\Unit\Emogrifier\HtmlProcessor;

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
        foreach ($classesExpectedToBeRemoved as $class) {
            self::assertNotContains($class, $result);
        }
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
            self::assertSame(
                $expectedInstanceCount,
                \substr_count($result, $class),
                'asserting \'' . $result . '\' contains ' . $expectedInstanceCount . ' instance(s) of "' . $class
                . '"'
            );
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
            self::assertNotRegExp('/^\\s|\\s{2}|\\s$/', $classAttributeValue);
        }
    }
}
