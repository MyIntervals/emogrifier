<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Utilities;

/**
 * Parses and stores a CSS document from a string of CSS, and provides methods to obtain the CSS in parts or as data
 * structures.
 *
 * @internal
 */
class CssDocument
{
    /**
     * @var \csstidy
     */
    private $csstidy;

    /**
     * @param string $css
     */
    public function __construct(string $css)
    {
        $this->csstidy = new \csstidy();
        $this->csstidy->parse($css);
    }

    /**
     * Collates the media query, selectors and declarations for individual rules from the parsed CSS, in order.
     *
     * @param array<array-key, string> $allowedMediaTypes
     *
     * @return array<int, array{media: string, selectors: string, declarations: string}>
     *         Array of string sub-arrays with the following keys:
     *         - "media" (the media query string, e.g. "@media screen and (max-width: 480px)",
     *           or an empty string if not from an `@media` rule);
     *         - "selectors" (the CSS selector(s), e.g., "*" or "h1, h2");
     *         - "declarations" (the semicolon-separated CSS declarations for that/those selector(s),
     *           e.g., "color: red; height: 4px;").
     */
    public function getStyleRulesData(array $allowedMediaTypes): array
    {
        $styleRulesData = [];

        /** @var array<string, array<string, string>> $rules */
        foreach ($this->csstidy->css as $atRuleWithoutBody => $rules) {
            if (\is_string($atRuleWithoutBody)) {
                if (!\preg_match('/^@media\\s++(.*+)$/is', $atRuleWithoutBody, $atRuleMatches)) {
                    continue;
                }
                $mediaQueryList = $atRuleMatches[1];
                [$mediaType] = \explode('(', $mediaQueryList, 2);
                if (\trim($mediaType) !== '') {
                    $mediaTypesExpression = \implode('|', $allowedMediaTypes);
                    if (!\preg_match('/^\\s*+(?:only\\s++)?+(?:' . $mediaTypesExpression . ')/i', $mediaType)) {
                        continue;
                    }
                }
                $media = '@media ' . $mediaQueryList;
            } else {
                $media = '';
            }

            foreach ($rules as $selector => $properties) {
                $styleRulesData[] = [
                    'media' => $media,
                    'selectors' => $selector,
                    'declarations' => $this->renderPropertyDeclarations($properties),
                ];
            }
        }

        return $styleRulesData;
    }

    /**
     * Renders at-rules from the parsed CSS that are valid and not conditional group rules (i.e. not rules such as
     * `@media` which contain style rules whose data is returned by {@see getStyleRulesData}).  Also does not render
     * `@charset` rules; these are discarded (only UTF-8 is supported).
     *
     * @return string
     */
    public function renderNonConditionalAtRules(): string
    {
        return $this->renderAtImportRules() . $this->renderNestedAtRules();
    }

    /**
     * @param array<string, string> $properties CSS property key-value pairs
     *
     * @return string declarations block
     */
    private function renderPropertyDeclarations(array $properties)
    {
        $declarations = [];
        foreach ($properties as $name => $value) {
            $declarations[] = \trim($name) . ': ' . $value;
        }

        return \implode(";\n", $declarations);
    }

    /**
     * @return string
     */
    private function renderAtImportRules(): string
    {
        $result = '';

        /** @var string $import */
        foreach ($this->csstidy->import as $import) {
            // Similar code exists in `csstidy_print::_print()`
            if (\substr($import, 0, 4) === 'url(' && \substr($import, -1, 1) === ')') {
                $import = '"' . \substr($import, 4, -1) . '"';
            } elseif (!\preg_match('/^".+"$/', $import)) {
                $import = '"' . $import . '"';
            }
            $result .= '@import ' . $import . ";\n";
        }

        return $result;
    }

    /**
     * `@media` rules are excluded.
     *
     * @return string
     */
    private function renderNestedAtRules(): string
    {
        $result = '';

        /** @var array<string, array<string, string>> $rules */
        foreach ($this->csstidy->css as $atRuleWithoutBody => $rules) {
            if (!\is_string($atRuleWithoutBody) || \preg_match('/^@media\\s/i', $atRuleWithoutBody)) {
                continue;
            }

            $ruleBody = '';
            foreach ($rules as $group => $properties) {
                if ($group === '@font-face' && !isset($properties['src'], $properties['font-family'])) {
                    continue;
                }
                $ruleBody .= $group . " {\n" . $this->renderPropertyDeclarations($properties) . "\n}\n";
            }

            $result .= \trim($atRuleWithoutBody) !== '' ? $atRuleWithoutBody . " {\n" . $ruleBody . "}\n" : $ruleBody;
        }

        return $result;
    }
}
