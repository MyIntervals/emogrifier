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
     * Emogrification may result in CSS or `style` property values that do not exactly match the input CSS but are
     * nonetheless equivalent.
     *
     * Notably, whitespace either side of "{", "}", ";", "," and (within a declarations block) ":", or at the beginning
     * of the CSS may be removed.  Other whitespace may be varied where equivalent (though not added or removed).
     *
     * This method helps takes care of that, by converting a CSS string into a regular expression part that will match
     * the equivalent CSS whilst allowing for such whitespace variation.  Thus, such nuances can be abstracted away from
     * the main tests, also allowing their test data to be written more humanly-readable with additional whitespace.
     *
     * @param string $css
     *
     * @return string Slashes (`/`) should be used as deliminters in the pattern composed using this.
     */
    protected static function getCssRegularExpressionMatcher(string $css): string
    {
        return \preg_replace_callback(
            self::CSS_REGULAR_EXPRESSION_PATTERN,
            [self::class, 'getCssRegularExpressionReplacement'],
            $css
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
