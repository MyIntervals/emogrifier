# Emogrifier

[![Build Status](https://travis-ci.org/jjriv/emogrifier.png?branch=master)](https://travis-ci.org/jjriv/emogrifier)

_n. e•mog•ri•fi•er [\ē-'mä-grƏ-,fī-Ər\] - a utility for changing completely the nature or appearance of HTML email,
esp. in a particularly fantastic or bizarre manner_

Emogrifier converts CSS styles into inline style attributes in your HTML code. This ensures proper display on email
and mobile device readers that lack stylesheet support.

This utility was developed as part of [Intervals](http://www.myintervals.com/) to deal with the problems posed by
certain email clients (namely Outlook 2007 and Google Gmail) when it comes to the way they handle styling contained
in HTML emails. As many web developers and designers already know, certain email clients are notorious for their
lack of CSS support. While attempts are being made to develop common
[email standards](http://www.email-standards.org/), implementation is still a ways off.

The primary problem with uncooperative email clients is that most tend to only regard inline CSS, discarding all
`<style>` elements and links to stylesheets in `<link>` elements. Emogrifier solves this problem by converting CSS
styles into inline style attributes in your HTML code.


## How it Works

Emogrifier automagically transmogrifies your HTML by parsing your CSS and inserting your CSS definitions into tags
within your HTML based on your CSS selectors.


## Usage

First, you provide Emogrifier with the HTML and CSS you would like to merge. This can happen directly during
instantiation:

    $html = '<html><h1>Hello world!</h1></html>';
    $css = 'h1 {font-size: 32px;}';
    $emogrifier = new \Pelago\Emogrifier($html, $css);

You could also use the setters for providing this data after instantiation:

    $emogrifier = new \Pelago\Emogrifier();

    $html = '<html><h1>Hello world!</h1></html>';
    $css = 'h1 {font-size: 32px;}';

    $emogrifier->setHtml($html);
    $emogrifier->setCss($css);

After you have set the HTML and CSS, you can call the `emogrify` method to merge both:

    $mergedHtml = $emogrifier->emogrify();


## Supported CSS selectors

Emogrifier currently support the following [CSS selectors](http://www.w3.org/TR/CSS2/selector.html):

 * ID
 * class
 * type
 * descendant
 * child
 * adjacent
 * attribute presence
 * attribute value

The following selectors are implemented, but currently are broken:

 * first-child (currently broken)
 * last-child (currently broken)

The following selectors are not implemented yet:

 * universal


## Caveats

* Emogrifier requires the HTML and the CSS to be UTF-8. Encodings like ISO8859-1 or ISO8859-15 are not supported.
* **NEW:** Emogrifier will grab existing inline style attributes _and_ will grab `<style>` blocks from your HTML, but it
  will not grab CSS files referenced in <link> elements (the problem email clients are going to ignore these tags
  anyway, so why leave them in your HTML?).
* Even with styles inline, certain CSS properties are ignored by certain email clients. For more information,
  review documentation [here](http://www.email-standards.org/).
* All CSS attributes that apply to a node will be applied, even if they are redundant. For example, if you define a
  font attribute _and_ a font-size attribute, both attributes will be applied to that node (in other words, the more
  specific attribute will not be combined into the more general attribute).
* There's a good chance you might encounter problems if your HTML is not well formed and valid (DOMDocument might
  complain). If you get problems like this, consider running your HTML through [Tidy](http://php.net/manual/en/book.tidy.php)
  before you pass it to Emogrifier.
* Finally, Emogrifier only supports CSS1 level selectors and a few CSS2 level selectors (but not all of them). It
  does not support pseudo selectors (Emogrifier works by converting CSS selectors to XPath selectors, and pseudo
  selectors cannot be converted accurately).


## Maintainer

Emogrifier is maintained by the good people at [Pelago](http://www.pelagodesign.com/), info AT pelagodesign DOT com.
