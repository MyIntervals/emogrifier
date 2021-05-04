<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Provides a method for use in constraints making assertions about CSS content where whitespace may vary.
 *
 * Due to {@link https://bugs.php.net/bug.php?id=75060 PHP 'bug' #75060}, traits cannot have constants, so, as a
 * workaround, this is currently implemented as a base class (but ideally would be a `trait`).
 */
abstract class CssConstraint extends Constraint
{
    /**
     * This regular expression pattern will match various parts of a string of CSS, and uses capturing groups to clarify
     * what, actually, has been matched.
     *
     * @var string
     */
    private const CSS_REGULAR_EXPRESSION_PATTERN = '/
        (?<![\\s;}])                        # - `}` as end of declarations rule block, captured in group 1, with
            (?:\\s*+;)?+                    #   possible surrounding whitespace and optional preceding `;` (but not if
            \\s*+(\\})\\s*+                 #   preceded by another `}` or `;`)
        |\\s*+(                             # - `{`, `}`, `;`, `,` or `:`, captured in group 2, with possible whitespace
            [{};,]                          #   around - `:` is only matched if in a declarations block (i.e. not
            |\\:(?![^\\{\\}]*+\\{)          #   followed later by `{` without a closing `}` first)
        )\\s*+                              #
        |(^\\s++)                           # - whitespace at the very start, captured in group 3
        |(>)\\s*+                           # - `>` (e.g. closing a `<style>` element opening tag) with optional
                                            #   whitespace following, captured in group 4
        |(\\s++)                            # - whitespace, captured in group 5
        |(?:                                # - Anything else is matched, though not captured.  This is required so that
            (?!                             #   any characters in the input string that happen to have a special meaning
                \\s*+(?:                    #   in a regular expression can be escaped.  `.` would also work, but
                    [{};,]                  #   matching a longer sequence is more optimal (and `.*` would not work).
                    |\\:(?![^\\{\\}]*+\\{)  #
                )                           #
                |\\s                        #
            )                               #
            [^>]                            #
        )++                                 #
    /x';

    /**
     * This is for matching a URL in a string that has already been converted from CSS into a regular expression pattern
     * to match it, thus needs to match special characters in their escaped form, and whitespace as `\s*+` (where
     * optional) or `\s++`.
     * It matches the end of the string, and optionally preceding whitespace or a semicolon surrounded by optional
     * whitespace, provided that what precedes is not a semicolon or whitespace.
     *
     * @var string
     */
    private const OPTIONAL_TRAILING_SEMICOLON_MATCHER_PATTERN
        = '/(?<!;)(?<!\\\\s[\\+\\*]\\+)(?:\\\\s\\*\\+;\\\\s\\*\\+|\\\\s\\+\\+)?+$/';

    /**
     * This is for matching a URL in a string that has already been converted from CSS into a regular expression pattern
     * to match it, thus needs to match special characters in their escaped form, and whitespace as `\s++`.  It matches
     * one or more characters which are not quotes, whitespace, a semicolon, or a closing parenthesis, optionally
     * enclosed in single or double quotes, and not beginning with `url(`.  The URL without the enclosing quotes is
     * captured in its second group (the first group is needed to match the optional opening quote so that the closing
     * quote can be matched).
     *
     * @var string
     */
    private const URL_MATCHER_MATCHER = '(?!url\\\\\\()([\'"]?+)((?:(?!\\\\s[\\+\\*]\\+|\\\\\\))[^\'";])++)\\g{-2}';

    /**
     * This is for matching a URL in a string that has already been converted from CSS into a regular expression pattern
     * to match it, thus needs to match special characters in their escaped form, and whitespace as `\s++`.  It matches
     * `@import` followed by whitespace then a URL which may or may not be enclosed in single or double quotes and/or
     * using the `url` CSS function.  The actual URL will be captured in the 2nd or 4th group, depending on whether the
     * `url` CSS function was used.
     *
     * @var string
     */
    private const AT_IMPORT_AND_URL_MATCHER_PATTERN = '/@import\\\\s\\+\\+(?:' . self::URL_MATCHER_MATCHER
        . '|url\\\\\\((?:\\\\s\\+\\+)?+' . self::URL_MATCHER_MATCHER . '(?:\\\\s\\+\\+)?+\\\\\\))/';

    /**
     * @see AT_IMPORT_AND_URL_MATCHER_PATTERN
     *
     * @var string
     */
    private const AT_IMPORT_URL_REPLACEMENT_MATCHER = '(?:([\'"]?+)$2$4\\g{-1})';

    /**
     * Emogrification may result in CSS or `style` property values that do not exactly match the input CSS but are
     * nonetheless equivalent.
     *
     * Notably, whitespace either side of "{", "}", ";", "," and (within a declarations block) ":", or at the beginning
     * of the CSS may be removed.  Other whitespace may be varied where equivalent (though not added or removed).
     *
     * Additionally, the parameter of an `@import` rule may be optionally enclosed in quotes or wrapped with the CSS
     * `url` function.
     *
     * This method helps takes care of that, by converting a CSS string into a regular expression part that will match
     * the equivalent CSS whilst allowing for such whitespace and other variation.  Thus, such nuances can be abstracted
     * away from the main tests, also allowing their test data to be written more humanly-readable with additional
     * whitespace.
     *
     * @param string $css
     *
     * @return string Slashes (`/`) should be used as deliminters in the pattern composed using this.
     */
    protected static function getCssRegularExpressionMatcher(string $css): string
    {
        $matcher = \preg_replace_callback(
            self::CSS_REGULAR_EXPRESSION_PATTERN,
            [self::class, 'getCssRegularExpressionReplacement'],
            $css
        );

        return self::getCssMatcherAllowingAtImportParameterVariation(
            self::getCssMatcherAllowingOptionalTrailingSemicolon($matcher, $css)
        );
    }

    /**
     * @param string[] $matches array of matches for {@see CSS_REGULAR_EXPRESSION_PATTERN}
     *
     * @return string replacement string, which may be `$matches[0]` if no alteration is needed
     */
    private static function getCssRegularExpressionReplacement(array $matches): string
    {
        if (($matches[1] ?? '') !== '') {
            $regularExpressionEquivalent = '(?:\\s*+;)?+\\s*+' . \preg_quote($matches[1], '/') . '\\s*+';
        } elseif (($matches[2] ?? '') !== '') {
            $regularExpressionEquivalent = '\\s*+' . \preg_quote($matches[2], '/') . '\\s*+';
        } elseif (($matches[3] ?? '') !== '') {
            $regularExpressionEquivalent = '\\s*+';
        } elseif (($matches[4] ?? '') !== '') {
            $regularExpressionEquivalent = \preg_quote($matches[4], '/') . '\\s*+';
        } elseif (($matches[5] ?? '') !== '') {
            $regularExpressionEquivalent = '\\s++';
        } else {
            $regularExpressionEquivalent = \preg_quote($matches[0], '/');
        }

        return $regularExpressionEquivalent;
    }

    /**
     * @param string $matcher
     *        regular expression part designed to match the CSS whilst allowing for whitespace and other syntactic
     *        variation
     * @param string $css original CSS to match
     *
     * @return string
     *         regular expression part which will also allow for an optional trailing semicolon if the CSS appears to
     *         consist only of property declarations
     */
    private static function getCssMatcherAllowingOptionalTrailingSemicolon(string $matcher, string $css): string
    {
        $isPropertyDeclarationsOnly = \strpos($css, ':') !== false && \preg_match('/[@\\{\\}]/', $css) === 0;

        if ($isPropertyDeclarationsOnly) {
            return \preg_replace(
                self::OPTIONAL_TRAILING_SEMICOLON_MATCHER_PATTERN,
                '(?:\\s*+;)?+',
                $matcher
            );
        }

        return $matcher;
    }

    /**
     * @param string $matcher
     *        regular expression part designed to match CSS whilst allowing for whitespace and other syntactic variation
     *
     * @return string
     *         regular expression part which will also allow the `@import` rule URL parameter to be enclosed in quotes
     *         and/or use the CSS `url` function (or not).
     */
    private static function getCssMatcherAllowingAtImportParameterVariation(string $matcher): string
    {
        return \preg_replace(
            self::AT_IMPORT_AND_URL_MATCHER_PATTERN,
            '@import\\s++(?:' . self::AT_IMPORT_URL_REPLACEMENT_MATCHER
                . '|url\\(\\s*+' . self::AT_IMPORT_URL_REPLACEMENT_MATCHER . '\\s*+\\))',
            $matcher
        );
    }

    /**
     * Invokes the parent class's constructor if there is one.  Since PHPUnit 8.x, `Constraint` (the parent class) does
     * not have a defined constructor.  But...
     *
     * In PHP, a class that may be extended should have a defined constructor, even if it does nothing.
     *
     * That way, a child class that needs a constructor can explicitly call its parent's constructor.  If it could not,
     * and then a newer version of the parent class needed a constructor, the newly added constructor would not be
     * invoked implicitly during instantiation of the child class.  In that eventuality, the child class would also need
     * updating, so there would be a compatibility issue, and potential for uncaught bugs if the child class's
     * constructor was not updated.
     */
    protected function __construct()
    {
        if (\is_callable('parent::__construct')) {
            parent::__construct();
        }
    }
}
