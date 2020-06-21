<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Support\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Provides a method for use in constraints making assertions about CSS content where whitespace may vary.
 *
 * Due to {@link https://bugs.php.net/bug.php?id=75060 PHP 'bug' #75060}, traits cannot have constants, so, as a
 * workaround, this is currently implemented as a base class (but ideally would be a `trait`).
 *
 * @author Jake Hotson <jake.github@qzdesign.co.uk>
 */
abstract class CssConstraint extends Constraint
{
    /**
     * This regular expression pattern will match various parts of a string of CSS, and uses capturing groups to clarify
     * what, actually, has been matched.
     *
     * @var string
     */
    private const CSS_REGEX_PATTERN = '/
        \\s*+([{},])\\s*+               # `{`, `}` or `,` captured in group 1, with possible whitespace either side
        |(^\\s++)                       # whitespace at the very start, captured in group 2
        |(>)\\s*+                       # `>` (e.g. closing a `<style>` element opening tag) with optional whitespace
                                        # following, captured in group 3
        |(?:(?!\\s*+[{},]|^\\s)[^>])++  # Anything else is captured in group 4.  There is a reason for this, which is
                                        # currently forgotten.  It has something to do with the way alternation works,
                                        # in terms of matching the longest pattern, or, if equal, the first, and that
                                        # the implementation in PHP does not quite behave as described on the tin.
    /x';

    /**
     * Processing of @media rules may involve removal of some unnecessary whitespace from the CSS placed in the <style>
     * element added to the document, due to the way that certain parts are `trim`med.  Notably, whitespace either side
     * of "{", "}" and "," or at the beginning of the CSS may be removed.
     *
     * This method helps takes care of that, by converting a search needle for an exact match into a regular expression
     * that allows for such whitespace removal, so that the tests themselves do not need to be written less humanly
     * readable and can use inputs containing extra whitespace.
     *
     * @param string $needle Needle that would be used with `assertContains` or `assertNotContains`.
     *
     * @return string Needle to use with `assertRegExp` or `assertNotRegExp` instead.
     */
    protected static function getCssNeedleRegularExpressionPattern(string $needle): string
    {
        $needleMatcher = \preg_replace_callback(
            self::CSS_REGEX_PATTERN,
            static function (array $matches): string {
                switch (true) {
                    case isset($matches[1]) && $matches[1] !== '':
                        return '\\s*+' . \preg_quote($matches[1], '/') . '\\s*+';
                    case isset($matches[2]) && $matches[2] !== '':
                        return '\\s*+';
                    case isset($matches[3]) && $matches[3] !== '':
                        return \preg_quote($matches[3], '/') . '\\s*+';
                    default:
                        return \preg_quote($matches[0], '/');
                }
            },
            $needle
        );
        return '/' . $needleMatcher . '/';
    }
}
