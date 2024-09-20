<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\HtmlProcessor;

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;
use Pelago\Emogrifier\Utilities\Preg;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Pelago\Emogrifier\HtmlProcessor\HtmlPruner
 */
final class HtmlPrunerTest extends TestCase
{
    /**
     * @test
     */
    public function fromHtmlReturnsInstanceOfCalledClass(): void
    {
        $subject = HtmlPruner::fromHtml('<html></html>');

        self::assertInstanceOf(HtmlPruner::class, $subject);
    }

    /**
     * @test
     */
    public function classIsAbstractHtmlProcessor(): void
    {
        $subject = HtmlPruner::fromHtml('<html></html>');

        self::assertInstanceOf(AbstractHtmlProcessor::class, $subject);
    }

    /**
     * @test
     */
    public function fromDomDocumentReturnsInstanceOfCalledClass(): void
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html></html>');
        $subject = HtmlPruner::fromDomDocument($document);

        self::assertInstanceOf(HtmlPruner::class, $subject);
    }

    /**
     * @test
     */
    public function removeElementsWithDisplayNoneProvidesFluentInterface(): void
    {
        $subject = HtmlPruner::fromHtml('<html></html>');

        $result = $subject->removeElementsWithDisplayNone();

        self::assertSame($subject, $result);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function displayNoneDataProvider(): array
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
    public function removeElementsWithDisplayNoneRemovesElementsWithDisplayNone(string $displayNone): void
    {
        $subject = HtmlPruner::fromHtml('<html><body><div style="' . $displayNone . '"></div></body></html>');

        $subject->removeElementsWithDisplayNone();

        self::assertStringNotContainsString('<div', $subject->renderBodyContent());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function provideEmogrifierKeepClassAttribute(): array
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
    public function removeElementsWithDisplayNoneNotRemovesElementsWithClassEmogrifierKeep(
        string $classAttributeValue
    ): void {
        $subject = HtmlPruner::fromHtml(
            '<html><body><div class="' . $classAttributeValue . '" style="display: none;"></div></body></html>'
        );

        $subject->removeElementsWithDisplayNone();

        self::assertStringContainsString('<div', $subject->renderBodyContent());
    }

    /**
     * @test
     */
    public function removeRedundantClassesProvidesFluentInterface(): void
    {
        $subject = HtmlPruner::fromHtml('<html></html>');

        $result = $subject->removeRedundantClasses();

        self::assertSame($subject, $result);
    }

    /**
     * @return array<string, array<int, array<int, string>>>
     */
    public function provideClassesToKeep(): array
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
     * @param array<int, string> $classesToKeep
     *
     * @dataProvider provideClassesToKeep
     */
    public function removeRedundantClassesPreservesHtmlWithoutClasses(array $classesToKeep): void
    {
        $html = '<p style="color: green;">hello</p>';
        $subject = HtmlPruner::fromHtml('<html>' . $html . '</html>');

        $subject->removeRedundantClasses($classesToKeep);

        self::assertStringContainsString($html, $subject->renderBodyContent());
    }

    /**
     * @return array<string, array{HTML: string, 'classes to keep': array<int, string>}>
     */
    public function provideHtmlAndNonMatchedClasses(): array
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
     * @param array<int, string> $classesToKeep
     *
     * @dataProvider provideHtmlAndNonMatchedClasses
     */
    public function removeRedundantClassesRemovesClassAttributesContainingNoClassesToKeep(
        string $html,
        array $classesToKeep
    ): void {
        $subject = HtmlPruner::fromHtml('<html>' . $html . '</html>');

        $subject->removeRedundantClasses($classesToKeep);

        self::assertStringNotContainsString('class', $subject->renderBodyContent());
    }

    /**
     * @return array<string, array{
     *             HTML: string,
     *             'classes to keep': array<int, string>,
     *             'classes expected to be removed': array<int, string>
     *         }>
     */
    public function provideHtmlAndSomeMatchedClasses(): array
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
     * @return array<string, array{
     *             HTML: string,
     *             'classes to keep': array<int, string>,
     *             'classes expected to be removed': array<int, string>
     *         }>
     */
    public function provideHtmlWithExtraWhitespaceAndSomeMatchedClasses(): array
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
     * @param array<int, string> $classesToKeep
     * @param array<int, string> $classesExpectedToBeRemoved
     *
     * @dataProvider provideHtmlAndSomeMatchedClasses
     * @dataProvider provideHtmlWithExtraWhitespaceAndSomeMatchedClasses
     */
    public function removeRedundantClassesRemovesClassesNotToKeep(
        string $html,
        array $classesToKeep,
        array $classesExpectedToBeRemoved
    ): void {
        $subject = HtmlPruner::fromHtml('<html>' . $html . '</html>');

        $subject->removeRedundantClasses($classesToKeep);

        $result = $subject->renderBodyContent();
        self::assertContainsNone($classesExpectedToBeRemoved, $result);
    }

    /**
     * @return array<string, array{HTML: string, 'classes to keep': array<int, string>}>
     */
    public function provideHtmlAndAllMatchedClasses(): array
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
     * @return array<string, array{HTML: string, 'classes to keep': array<int, string>}>
     */
    public function provideHtmlWithExtraWhitespaceAndAllMatchedClasses(): array
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
     * @param array<int, string> $classesToKeep
     *
     * @dataProvider provideHtmlAndSomeMatchedClasses
     * @dataProvider provideHtmlAndAllMatchedClasses
     * @dataProvider provideHtmlWithExtraWhitespaceAndSomeMatchedClasses
     * @dataProvider provideHtmlWithExtraWhitespaceAndAllMatchedClasses
     */
    public function removeRedundantClassesNotRemovesClassesToKeep(string $html, array $classesToKeep): void
    {
        $subject = HtmlPruner::fromHtml('<html>' . $html . '</html>');

        $subject->removeRedundantClasses($classesToKeep);

        $result = $subject->renderBodyContent();
        foreach ($classesToKeep as $class) {
            $expectedInstanceCount = \substr_count($html, $class);
            self::assertSubstringCount($expectedInstanceCount, $result, $class);
        }
    }

    /**
     * @test
     *
     * @param string $html
     * @param array<int, string> $classesToKeep
     *
     * @dataProvider provideHtmlWithExtraWhitespaceAndSomeMatchedClasses
     * @dataProvider provideHtmlWithExtraWhitespaceAndAllMatchedClasses
     */
    public function removeRedundantClassesMinifiesClassAttributes(string $html, array $classesToKeep): void
    {
        $subject = HtmlPruner::fromHtml('<html>' . $html . '</html>');

        $subject->removeRedundantClasses($classesToKeep);

        (new Preg())->matchAll('/class="([^"]*+)"/', $subject->renderBodyContent(), $classAttributeMatches);
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
     * @return array{0: HtmlPruner, 1: CssInliner}
     */
    private function buildSubjectAndCssInlinerWithCssInlined(string $html, string $css): array
    {
        $cssInliner = CssInliner::fromHtml($html);
        $cssInliner->inlineCss($css);

        $subject = HtmlPruner::fromDomDocument($cssInliner->getDomDocument());

        return [$subject, $cssInliner];
    }

    /**
     * @test
     */
    public function removeRedundantClassesAfterCssInlinedProvidesFluentInterface(): void
    {
        [$subject, $cssInliner] = $this->buildSubjectAndCssInlinerWithCssInlined('<html></html>', '');

        $result = $subject->removeRedundantClassesAfterCssInlined($cssInliner);

        self::assertSame($subject, $result);
    }

    /**
     * @test
     */
    public function removeRedundantClassesAfterCssInlinedThrowsExceptionIfInlineCssNotCalled(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $cssInliner = CssInliner::fromHtml('<html></html>');
        $subject = HtmlPruner::fromDomDocument($cssInliner->getDomDocument());

        $subject->removeRedundantClassesAfterCssInlined($cssInliner);
    }

    /**
     * @return array<string, array{HTML: string, CSS: string, 'classes expected to be removed': array<int, string>}>
     */
    public function provideClassesNotInUninlinableRules(): array
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
     * @param array<int, string> $classesExpectedToBeRemoved
     *
     * @dataProvider provideClassesNotInUninlinableRules
     */
    public function removeRedundantClassesAfterCssInlinedRemovesClassesNotInUninlinableRules(
        string $html,
        string $css,
        array $classesExpectedToBeRemoved = []
    ): void {
        [$subject, $cssInliner] = $this->buildSubjectAndCssInlinerWithCssInlined('<html>' . $html . '</html>', $css);

        $subject->removeRedundantClassesAfterCssInlined($cssInliner);

        $result = $subject->renderBodyContent();
        self::assertContainsNone($classesExpectedToBeRemoved, $result);
    }

    /**
     * @return array<string, array{HTML: string, CSS: string, 'classes to be kept': array<int, string>}>
     */
    public function provideClassesInUninlinableRules(): array
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
     * @param array<int, string> $classesToBeKept
     *
     * @dataProvider provideClassesInUninlinableRules
     */
    public function removeRedundantClassesAfterCssInlinedNotRemovesClassesInUninlinableRules(
        string $html,
        string $css,
        array $classesToBeKept = []
    ): void {
        [$subject, $cssInliner] = $this->buildSubjectAndCssInlinerWithCssInlined('<html>' . $html . '</html>', $css);

        $subject->removeRedundantClassesAfterCssInlined($cssInliner);

        $result = $subject->renderBodyContent();
        self::assertContainsAll($classesToBeKept, $result);
    }

    /**
     * Asserts that none of the `$needles` can be found within the string `$haystack`.
     *
     * @param array<int, string> $needles
     * @param string $haystack
     *
     * @throws ExpectationFailedException
     */
    private static function assertContainsNone(array $needles, string $haystack): void
    {
        foreach ($needles as $needle) {
            self::assertStringNotContainsString($needle, $haystack);
        }
    }

    /**
     * Asserts that all of the `$needles` can be found within the string `$haystack`.
     *
     * @param array<int, string> $needles
     * @param string $haystack
     *
     * @throws ExpectationFailedException
     */
    private static function assertContainsAll(array $needles, string $haystack): void
    {
        foreach ($needles as $needle) {
            self::assertStringContainsString($needle, $haystack);
        }
    }

    /**
     * Asserts that the number of occurrences of `$needle` within the string `$haystack` is as expected.
     *
     * @param int $expectedCount
     * @param string $haystack
     * @param string $needle
     *
     * @throws ExpectationFailedException
     */
    private static function assertSubstringCount(int $expectedCount, string $haystack, string $needle): void
    {
        self::assertSame(
            $expectedCount,
            \substr_count($haystack, $needle),
            'asserting \'' . $haystack . '\' contains ' . (string) $expectedCount . ' instance(s) of "' . $needle . '"'
        );
    }

    /**
     * Asserts that a string does not contain consecutive whitespace characters, or begin or end with whitespace.
     *
     * @param string $string
     *
     * @throws ExpectationFailedException
     */
    private static function assertMinified(string $string): void
    {
        self::assertDoesNotMatchRegularExpression('/^\\s|\\s{2}|\\s$/', $string);
    }
}
