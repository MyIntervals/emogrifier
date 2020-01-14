<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\HtmlProcessor;

/**
 * Parse a HTML document fragment and add missing root elements.
 *
 * @author Kieran Brahney <kieran@supportpal.com>
 */
class HtmlParser
{
    /**
     * Structure of a basic HTML document.
     *
     * @var array
     */
    protected $tree = [
        'doctype' => '',
        'html'    => [
            'start'   => '<html>',
            'end'     => '</html>',
            'content' => [],
        ],
        'head'    => [
            'start'   => '<head>',
            'end'     => '</head>',
            'content' => [],
        ],
        'body'    => [
            'start'   => '<body>',
            'end'     => '</body>',
            'content' => [],
        ],
    ];

    /**
     * What root element did we last add to.
     *
     * @var string|null
     */
    protected $previousKey = null;

    /**
     * Parse a HTML document.
     *
     * @param  string  $html
     */
    public function loadHtml(string $html)
    {
        $i = 0;
        $len = \strlen($html);
        while ($i < $len) {
            if ((string)$html[$i] == '<') {
                // Found a tag, get chars until the end of the tag.
                $tag = '';
                while ($i < $len && (string)$html[$i] != '>') {
                    $tag .= $html[$i++];
                }

                if ($i < $len && (string)$html[$i] == '>') {
                    $tag .= $html[$i++];

                    // Copy any whitespace following the tag.
                    // Anything added here needs to be added to the rtrim in the nodeName function.
                    while ($i < $len && \preg_match('/\s/', (string)$html[$i])) {
                        $tag .= $html[$i++];
                    }
                } else {
                    // Missing closing tag?
                    $tag .= '>';
                }

                $this->addToTree($tag);
            } else {
                $this->addToTree($html[$i++]);
            }
        }
    }

    /**
     * Format the document in a structured way (ensures root elements exists and moves scripts/css into <body>).
     *
     * @return string
     */
    public function saveHtml()
    {
        // Initialise buffer.
        $buffer = '';

        // Add <!DOCTYPE> - this is optional.
        $buffer .= (string)$this->tree['doctype'];

        // Add <html>
        $buffer .= (string)$this->tree['html']['start'];

        // Add head
        $buffer .= (string)$this->tree['head']['start'];
        foreach ((array)$this->tree['head']['content'] as $node) {
            $buffer .= (string)$node;
        }
        $buffer .= (string)$this->tree['head']['end'];

        // Add body
        $buffer .= $this->tree['body']['start'];
        foreach ((array)$this->tree['body']['content'] as $node) {
            $buffer .= (string)$node;
        }
        $buffer .= (string)$this->tree['body']['end'];

        // Close </html> tag
        return $buffer . (string)$this->tree['html']['end'];
    }

    /**
     * Add a node into the tree for the correct parent.
     *
     * @param string  $node
     *
     * @return void
     */
    protected function addToTree(string $node)
    {
        if ($node[0] == '<') {
            switch (\strtolower($this->nodeName($node))) {
                case '!doctype':
                    if (empty($this->tree['doctype'])) {
                        $this->tree['doctype'] = $node;

                        return;
                    }

                    // Don't overwrite if we've already got a doctype definition.
                    return;

                case 'html':
                    $this->addTo('html', $node, false);
                    return;

                case 'head':
                    $this->addTo('head', $node, true);
                    return;

                default:
                    $this->addTo($this->previousKey ?? 'body', $node, true);
                    return;
            }
        }

        // text node
        $this->addTo($this->previousKey ?? 'body', $node, true);
    }

    /**
     * Add a node to the the tree.
     *
     * @param  string  $key
     * @param  string  $node
     * @param  bool    $setPrevious
     *
     * @return void
     */
    protected function addTo(string $key, string $node, bool $setPrevious)
    {
        $previousKey = $key;

        if (\stripos($node, '<' . $key) !== false) {
            $this->tree[$key]['start'] = $node;
        } elseif (\stristr($node, '/' . $key . '>')) {
            $this->tree[$key]['end'] = $node;
            $previousKey = null;
        } else {
            $this->tree[$key]['content'][] = $node;
        }

        if ($setPrevious) {
            $this->previousKey = $previousKey;
        }
    }

    /**
     * Get the name of a node without </>
     *
     * @param  string  $node
     *
     * @return string
     */
    protected function nodeName(string $node)
    {
        $name = \preg_replace('/>\s*/', '', \ltrim($node, '</'));

        return \explode(' ', $name)[0];
    }
}
