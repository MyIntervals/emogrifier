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
}
