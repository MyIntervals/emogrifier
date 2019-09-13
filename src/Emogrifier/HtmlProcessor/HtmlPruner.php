<?php

namespace Pelago\Emogrifier\HtmlProcessor;

/**
 * This class can remove things from HTML.
 *
 * @author Oliver Klee <github@oliverklee.de>
 */
class HtmlPruner extends AbstractHtmlProcessor
{
    /**
     * We need to look for display:none, but we need to do a case-insensitive search. Since DOMDocument only
     * supports XPath 1.0, lower-case() isn't available to us. We've thus far only set attributes to lowercase,
     * not attribute values. Consequently, we need to translate() the letters that would be in 'NONE' ("NOE")
     * to lowercase.
     *
     * @var string
     */
    const DISPLAY_NONE_MATCHER = '//*[contains(translate(translate(@style," ",""),"NOE","noe"),"display:none")]';

    /**
     * Removes elements that have a "display: none;" style.
     *
     * @return self fluent interface
     */
    public function removeElementsWithDisplayNone()
    {
        $elementsWithStyleDisplayNone = $this->xPath->query(self::DISPLAY_NONE_MATCHER);
        if ($elementsWithStyleDisplayNone->length === 0) {
            return $this;
        }

        /** @var \DOMNode $element */
        foreach ($elementsWithStyleDisplayNone as $element) {
            $parentNode = $element->parentNode;
            if ($parentNode !== null) {
                $parentNode->removeChild($element);
            }
        }

        return $this;
    }

    /**
     * Removes classes that are no longer required (e.g. because there are no longer any CSS rules that reference them)
     * from `class` attributes.
     *
     * Note that this does not inspect the CSS, but expects to be provided with a list of classes that are still in use.
     *
     * This method also has the (presumably beneficial) side-effect of minifying (removing superfluous whitespace from)
     * `class` attributes.
     *
     * @param string[] $classesToKeep list of class names that should not be removed
     *
     * @return self fluent interface
     */
    public function removeRedundantClasses(array $classesToKeep)
    {
        $nodesWithClassAttribute = $this->xPath->query('//*[@class]');

        if ($classesToKeep !== []) {
            // https://stackoverflow.com/questions/6329211/php-array-intersect-efficiency
            // It's more efficient to invert the array and use `array_intersect_key()` when doing many intersections.
            $classesToKeepAsKeys = \array_flip($classesToKeep);

            foreach ($nodesWithClassAttribute as $node) {
                $nodeClasses = \preg_split('/\\s++/', \trim($node->getAttribute('class')));
                $nodeClassesToKeep = \array_flip(\array_intersect_key(
                    \array_flip($nodeClasses),
                    $classesToKeepAsKeys
                ));
                if ($nodeClassesToKeep !== []) {
                    $node->setAttribute('class', \implode(' ', $nodeClassesToKeep));
                } else {
                    $node->removeAttribute('class');
                }
            }
        } else {
            foreach ($nodesWithClassAttribute as $node) {
                $node->removeAttribute('class');
            }
        }

        return $this;
    }
}
