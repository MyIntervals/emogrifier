<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Css;

/**
 * This class represents a CSS style rule, including a media query, selectors and a declarations block.
 *
 * @internal
 */
class StyleRule
{
    /**
     * @var string
     */
    private $mediaQuery;

    /**
     * @var array<int, string>
     */
    private $selectors;

    /**
     * @var string
     */
    private $declarationsBlock;

    /**
     * @param string $mediaQuery the media query, e.g. `@media screen and (max-width: 480px)`, or an empty string
     * @param string $selectors the comma-separated selectors, e.g., `*` or `h1, h2`, must not be empty
     * @param string $declarationsBlock the comma-separated selectors, e.g., `*` or `h1, h2`, may be empty
     *
     * @throws \InvalidArgumentException if $selectors is empty (or whitespace only)
     */
    public function __construct(string $mediaQuery, string $selectors, string $declarationsBlock)
    {
        if (\trim($selectors) === '') {
            throw new \InvalidArgumentException('Please provide non-empty selectors.', 1623263716);
        }

        $this->mediaQuery = \trim($mediaQuery);
        $this->selectors = \array_map('trim', \explode(',', $selectors));
        $this->declarationsBlock = \trim($declarationsBlock);
    }

    /**
     * @returns string the media query, e.g. `@media screen and (max-width: 480px)`, or an empty string
     */
    public function getMediaQuery(): string
    {
        return $this->mediaQuery;
    }

    /**
     * Checks whether the media query is non-empty and has any non-whitespace characters.
     */
    public function hasNonEmptyMediaQuery(): bool
    {
        return $this->getMediaQuery() !== '';
    }

    /**
     * @return array<int, string> the selectors, e.g, `["h1", "h2"]`
     */
    public function getSelectors(): array
    {
        return $this->selectors;
    }

    /**
     * @return string the semicolon-separated CSS declarations, e.g., `color: red; height: 4px;`
     */
    public function getDeclarationsBlock(): string
    {
        return $this->declarationsBlock;
    }

    /**
     * Checks whether the declarations block is non-empty and has any non-whitespace characters.
     */
    public function hasNonEmptyDeclarationsBlock(): bool
    {
        return $this->getDeclarationsBlock() !== '';
    }
}
