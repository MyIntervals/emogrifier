<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Utilities;

/**
 * Provides a common method for parsing CSS declaration blocks.
 * These might be from actual CSS, or from the `style` attribute of an HTML DOM element.
 *
 * Caches results globally.
 *
 * @internal
 */
class DeclarationBlockParser
{
    /**
     * @var array<string, array<string, string>>
     */
    private static $cache = [];

    /**
     * Parses a CSS declaration block into property name/value pairs.
     *
     * Example:
     *
     * The declaration block
     *
     *   "color: #000; font-weight: bold;"
     *
     * will be parsed into the following array:
     *
     *   "color" => "#000"
     *   "font-weight" => "bold"
     *
     * @param string $declarationBlock the CSS declarations block without the curly braces, may be empty
     *
     * @return array<string, string>
     *         the CSS declarations with the property names as array keys and the property values as array values
     */
    public function parse(string $declarationBlock): array
    {
        if (isset(self::$cache[$declarationBlock])) {
            return self::$cache[$declarationBlock];
        }

        $preg = new Preg();

        $declarations = $preg->split('/;(?!base64|charset)/', $declarationBlock);

        $properties = [];
        foreach ($declarations as $declaration) {
            $matches = [];
            if (
                $preg->match(
                    '/^([A-Za-z\\-]+)\\s*:\\s*(.+)$/s',
                    \trim($declaration),
                    $matches
                )
                === 0
            ) {
                continue;
            }

            $propertyName = \strtolower($matches[1]);
            $propertyValue = $matches[2];
            $properties[$propertyName] = $propertyValue;
        }
        self::$cache[$declarationBlock] = $properties;

        return $properties;
    }
}
