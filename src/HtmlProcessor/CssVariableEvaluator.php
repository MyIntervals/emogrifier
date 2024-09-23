<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\HtmlProcessor;

use Pelago\Emogrifier\Utilities\DeclarationBlockParser;
use Pelago\Emogrifier\Utilities\Preg;

/**
 * This class can evaluate CSS custom properties that are defined and used in inline style attributes.
 */
class CssVariableEvaluator extends AbstractHtmlProcessor
{
    /**
     * temporary collection used by {@see replaceVariablesInDeclarations} and callee methods
     *
     * @var array<non-empty-string, string>
     */
    private $currentVariableDefinitions = [];

    /**
     * Replaces all CSS custom property references in inline style attributes with their corresponding values where
     * defined in inline style attributes (either from the element itself or the nearest ancestor).
     *
     * @throws \UnexpectedValueException
     *
     * @return $this
     */
    public function evaluateVariables(): self
    {
        return $this->evaluateVaraiblesInElementAndDescendants($this->getHtmlElement(), []);
    }

    /**
     * @param array<non-empty-string, string> $declarations
     *
     * @return array<non-empty-string, string>
     */
    private function getVaraibleDefinitionsFromDeclarations(array $declarations): array
    {
        return \array_filter(
            $declarations,
            static function (string $key): bool {
                return \substr($key, 0, 2) === '--';
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Callback function for {@see replaceVariablesInPropertyValue} performing regular expression replacement.
     *
     * @param array<int, string> $matches
     */
    private function getPropertyValueReplacement(array $matches): string
    {
        $variableName = $matches[1];
        if (isset($this->currentVariableDefinitions[$variableName])) {
            return $this->currentVariableDefinitions[$variableName];
        } else {
            $fallbackValueSeparator = $matches[2] ?? '';
            if ($fallbackValueSeparator !== '') {
                $fallbackValue = $matches[3];
                // The fallback value may use other CSS variables, so recurse
                return $this->replaceVariablesInPropertyValue($fallbackValue);
            } else {
                return $matches[0];
            }
        }
    }

    /**
     * Regular expression based on {@see https://stackoverflow.com/a/54143883/2511031 a StackOverflow answer}.
     */
    private function replaceVariablesInPropertyValue(string $propertyValue): string
    {
        return (new Preg())->replaceCallback(
            '/
                var\\(
                    \\s*+
                    # capture variable name including `--` prefix
                    (
                        --[^\\s\\),]++
                    )
                    \\s*+
                    # capture optional fallback value
                    (?:
                        # capture separator to confirm there is a fallback value
                        (,)\\s*
                        # begin capture with named group that can be used recursively
                        (?<recursable>
                            # begin named group to match sequence without parentheses, except in strings
                            (?<noparentheses>
                                # repeated zero or more times:
                                (?:
                                    # sequence without parentheses or quotes
                                    [^\\(\\)\'"]++
                                    |
                                    # string in double quotes
                                    "(?>[^"\\\\]++|\\\\.)*"
                                    |
                                    # string in single quotes
                                    \'(?>[^\'\\\\]++|\\\\.)*\'
                                )*+
                            )
                            # repeated zero or more times:
                            (?:
                                # sequence in parentheses
                                \\(
                                    # using the named recursable pattern
                                    (?&recursable)
                                \\)
                                # sequence without parentheses, except in strings
                                (?&noparentheses)
                            )*+
                        )
                    )?+
                \\)
            /x',
            \Closure::fromCallable([$this, 'getPropertyValueReplacement']),
            $propertyValue
        );
    }

    /**
     * @param array<non-empty-string, string> $declarations
     *
     * @return array<non-empty-string, string>|false `false` is returned if no substitutions were made.
     */
    private function replaceVariablesInDeclarations(array $declarations)
    {
        $substitutionsMade = false;
        $result = \array_map(
            function (string $propertyValue) use (&$substitutionsMade): string {
                $newPropertyValue = $this->replaceVariablesInPropertyValue($propertyValue);
                if ($newPropertyValue !== $propertyValue) {
                    $substitutionsMade = true;
                }
                return $newPropertyValue;
            },
            $declarations
        );

        return $substitutionsMade ? $result : false;
    }

    /**
     * @param array<non-empty-string, string> $declarations;
     */
    private function getDeclarationsAsString(array $declarations): string
    {
        $declarationStrings = \array_map(
            static function (string $key, string $value): string {
                return $key . ': ' . $value;
            },
            \array_keys($declarations),
            \array_values($declarations)
        );

        return \implode('; ', $declarationStrings) . ';';
    }

    /**
     * @param array<non-empty-string, string> $ancestorVariableDefinitions
     *
     * @return $this
     */
    private function evaluateVaraiblesInElementAndDescendants(
        \DOMElement $element,
        array $ancestorVariableDefinitions
    ): self {
        $style = $element->getAttribute('style');

        // Avoid parsing declarations if none use or define a variable
        if ((new Preg())->match('/(?<![\\w\\-])--[\\w\\-]/', $style) !== 0) {
            $declarations = (new DeclarationBlockParser())->parse($style);
            $variableDefinitions = $this->currentVariableDefinitions
                = $this->getVaraibleDefinitionsFromDeclarations($declarations) + $ancestorVariableDefinitions;

            $newDeclarations = $this->replaceVariablesInDeclarations($declarations);
            if ($newDeclarations !== false) {
                $element->setAttribute('style', $this->getDeclarationsAsString($newDeclarations));
            }
        } else {
            $variableDefinitions = $ancestorVariableDefinitions;
        }

        for ($child = $element->firstElementChild; $child !== null; $child = $child->nextElementSibling) {
            $this->evaluateVaraiblesInElementAndDescendants($child, $variableDefinitions);
        }

        return $this;
    }
}
