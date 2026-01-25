<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\preg_replace_callback;

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
        |(\\#[0-9A-Fa-f]++\\b)              # - RGB colour property value, captured in group 6, if in a declarations
            (?![^\\{\\}]*+\\{)              #   block (i.e. not followed later by `{` without a closing `}` first)
        |@(?i:import)\\s++                  # - `@import` (case insensitive) followed by whitespace and a URL,
            (?:                             #   optionally enclosed in quotes and optionally using the CSS `url`
                (?!url\\()                  #   function, provided the URL is not empty and does not contain whitespace,
                ([\'"]?+)                   #   quotes, a closing bracket or semicolon, with the URL captured in group 8
                ([^\'"\\s\\);]++)           #   or 10 depending on whether the CSS `url` function was used (and the
                \\g{-2}                     #   opening quote, if any, captured in group 7 or 9)
            |                               #
                url\\(\\s*+                 #
                ([\'"]?+)                   #
                ([^\'"\\s\\);]++)           #
                \\g{-2}                     #
                \\s*+\\)                    #
            )                               #
        |\\burl\\(\\s*+                     # - CSS `url` function, with optional quote captured in group 11, and
            ([\'"]?+)                       #   (non-empty) URL value in group 12, provided the URL does not contain
            ([^\'"\\(\\)\\s]++)             #   quotes, parentheses or whitespace
            \\g{-2}                         #
            \\s*+\\)                        #
        |(?<=[\\s:])                        # - singly or doubly quoted string, surrounded by whitespace or delimiters
            ([\'"])                         #   of a property value (i.e. a colon is allowed before, and a semicolon or
            ([^\'"]*+)                      #   closing curly brace is allowed after), with the quote captured in group
            \\g{-2}                         #   13 and the string in group 14
            (?=[\\s;\\}])                   #
        |(?<!\\w)0?+(\\.)(?=\\d)            # - start of decimal number less than 1 - optional `0` then decimal point
                                            #   (captured in group 15), provided followed by a digit
        |@                                  # - at-rule name, captured in group 16, along with preceding `@`, provided
            (?!(?i:charset)[^\\w\\-])       #   followed by character not part of at-rule name which is not `charset`
            ([\\w\\-]++)(?=[^\\w\\-])       #
        |(?:                                # - Anything else is matched, though not captured.  This is required so that
            (?!                             #   any characters in the input string that happen to have a special meaning
                \\s*+(?:                    #   in a regular expression can be escaped.  `.` would also work, but
                    [{};,]                  #   matching a longer sequence is more optimal (and `.*` would not work).
                    |\\:(?![^\\{\\}]*+\\{)  #
                )                           #
                |\\s                        #
                |(\\#[0-9A-Fa-f]++\\b)      #
                    (?![^\\{\\}]*+\\{)      #
                |@(?i:import)\\s++          #
                    (?:                     #
                        (?!url\\()          #
                        ([\'"]?+)           #
                        [^\'"\\s\\);]++     #
                        \\g{-1}             #
                    |                       #
                        url\\(\\s*+         #
                        ([\'"]?+)           #
                        [^\'"\\s\\);]++     #
                        \\g{-1}             #
                        \\s*+\\)            #
                    )                       #
                |\\burl\\(\\s*+([\'"]?+)    #
                    [^\'"\\(\\)\\s]++       #
                    \\g{-1}\\s*+\\)         #
                |(?<=[\\s:])([\'"])         #
                    [^\'"]++                #
                    \\g{-1}(?=[\\s;\\}])    #
                |(?<!\\w)0?+\\.(?=\\d)      #
                |@(?!(?i:charset)[^\\w\\-]) #
                    [\\w\\-]++(?=[^\\w\\-]) #
            )                               #
            [^>]                            #
        )++                                 #
    /x';

    private const URL_REPLACEMENT_MATCHER = '(?:([\'"]?+)$8$10\\g{-1})';

    private const AT_IMPORT_URL_REPLACEMENT_MATCHER = '@(?i:import)\\s++(?:' . self::URL_REPLACEMENT_MATCHER
        . '|url\\(\\s*+' . self::URL_REPLACEMENT_MATCHER . '\\s*+\\))';

    /**
     * Regular expression replacements for parts of the CSS matched by {@see CSS_REGULAR_EXPRESSION_PATTERN}, indexed by
     * capturing group upon which capturing a non-empty string means the corresponding replacement should be selected.
     */
    private const CSS_REGULAR_EXPRESSION_REPLACEMENTS = [
        1 => '(?:\\s*+;)?+\\s*+$1\\s*+',
        2 => '\\s*+$2\\s*+',
        3 => '\\s*+',
        4 => '$4\\s*+',
        5 => '\\s++',
        6 => '(?i:$6)',
        8 => self::AT_IMPORT_URL_REPLACEMENT_MATCHER,
        10 => self::AT_IMPORT_URL_REPLACEMENT_MATCHER,
        12 => 'url\\(\\s*+([\'"]?+)$12\\g{-1}\\s*+\\)',
        13 => '([\'"])$14\\g{-1}',
        15 => '0?+\\.',
        16 => '@(?i:$16)',
    ];

    /**
     * This is for matching a string that has already been converted from CSS into a regular expression pattern to match
     * it, thus needs to match special characters in their escaped form, and whitespace as `\s*+` (where optional) or
     * `\s++`.
     * It matches the end of the string, and optionally preceding whitespace or a semicolon surrounded by optional
     * whitespace, provided that what precedes is not a semicolon or whitespace.
     */
    private const OPTIONAL_TRAILING_SEMICOLON_MATCHER_PATTERN
        = '/(?<!;)(?<!\\\\s[\\+\\*]\\+)(?:\\\\s\\*\\+;\\\\s\\*\\+|\\\\s\\+\\+)?+$/';

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
     * @return string Slashes (`/`) should be used as delimiters in the pattern composed using this.
     */
    protected static function getCssRegularExpressionMatcher(string $css): string
    {
        $callback = static function (array $matches): string {
            /** @var array<int<0, max>, string> $matches */
            return self::getCssRegularExpressionReplacement($matches);
        };
        if (\function_exists('Safe\\preg_replace_callback')) {
            $matcher = preg_replace_callback(self::CSS_REGULAR_EXPRESSION_PATTERN, $callback, $css);
        } else {
            // @phpstan-ignore-next-line The safe version is only available in "thecodingmachine/safe" for PHP >= 8.1.
            $matcher = \preg_replace_callback(self::CSS_REGULAR_EXPRESSION_PATTERN, $callback, $css);
        }
        \assert(\is_string($matcher));

        return self::getCssMatcherAllowingOptionalTrailingSemicolon($matcher, $css);
    }

    /**
     * @param array<int<0, max>, string> $matches matches for {@see CSS_REGULAR_EXPRESSION_PATTERN}
     *
     * @return string replacement string, which may be `$matches[0]` if no alteration is needed
     */
    private static function getCssRegularExpressionReplacement(array $matches): string
    {
        $regularExpressionEquivalent = null;

        $pattern = '/\\$(\\d++)/';
        $callback = static function (array $referenceMatches) use ($matches): string {
            $index = $referenceMatches[1];
            \assert(\is_string($index));

            return \preg_quote($matches[$index] ?? '', '/');
        };
        foreach (self::CSS_REGULAR_EXPRESSION_REPLACEMENTS as $index => $replacement) {
            if (($matches[$index] ?? '') !== '') {
                if (\function_exists('Safe\\preg_replace_callback')) {
                    $regularExpressionEquivalent = preg_replace_callback($pattern, $callback, $replacement);
                } else {
                    // @phpstan-ignore-next-line The safe version in "thecodingmachine/safe" needs PHP >= 8.1.
                    $regularExpressionEquivalent = \preg_replace_callback($pattern, $callback, $replacement);
                }
                \assert(\is_string($regularExpressionEquivalent));
                break;
            }
        }

        if (!\is_string($regularExpressionEquivalent)) {
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
        $isPropertyDeclarationsOnly = \strpos($css, ':') !== false && preg_match('/[@\\{\\}]/', $css) === 0;
        if ($isPropertyDeclarationsOnly) {
            return preg_replace(
                self::OPTIONAL_TRAILING_SEMICOLON_MATCHER_PATTERN,
                '(?:\\s*+;)?+',
                $matcher
            );
        }

        return $matcher;
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
        if (\is_callable([parent::class, '__construct'])) {
            parent::__construct();
        }
    }
}
