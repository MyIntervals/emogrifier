# Emogrifier

[![Build Status](https://travis-ci.org/MyIntervals/emogrifier.svg?branch=master)](https://travis-ci.org/MyIntervals/emogrifier)
[![Latest Stable Version](https://poser.pugx.org/pelago/emogrifier/v/stable.svg)](https://packagist.org/packages/pelago/emogrifier)
[![Total Downloads](https://poser.pugx.org/pelago/emogrifier/downloads.svg)](https://packagist.org/packages/pelago/emogrifier)
[![Latest Unstable Version](https://poser.pugx.org/pelago/emogrifier/v/unstable.svg)](https://packagist.org/packages/pelago/emogrifier)
[![License](https://poser.pugx.org/pelago/emogrifier/license.svg)](https://packagist.org/packages/pelago/emogrifier)

_n. e•mog•ri•fi•er [\ē-'mä-grƏ-,fī-Ər\] - a utility for changing completely the
nature or appearance of HTML email, esp. in a particularly fantastic or bizarre
manner_

Emogrifier converts CSS styles into inline style attributes in your HTML code.
This ensures proper display on email and mobile device readers that lack
stylesheet support.

This utility was developed as part of [Intervals](http://www.myintervals.com/)
to deal with the problems posed by certain email clients (namely Outlook 2007
and GoogleMail) when it comes to the way they handle styling contained in HTML
emails. As many web developers and designers already know, certain email
clients are notorious for their lack of CSS support. While attempts are being
made to develop common [email standards](http://www.email-standards.org/),
implementation is still a ways off.

The primary problem with uncooperative email clients is that most tend to only
regard inline CSS, discarding all `<style>` elements and links to stylesheets
in `<link>` elements. Emogrifier solves this problem by converting CSS styles
into inline style attributes in your HTML code.

- [How it works](#how-it-works)
- [Installation](#installation)
- [Usage](#usage)
- [Supported CSS selectors](#supported-css-selectors)
- [Caveats](#caveats)
- [Steps to release a new version](#steps-to-release-a-new-version)
- [Maintainers](#maintainers)

## How it Works

Emogrifier automagically transmogrifies your HTML by parsing your CSS and
inserting your CSS definitions into tags within your HTML based on your CSS
selectors.

## Installation

For installing emogrifier, either add `pelago/emogrifier` to the `require`
section in your project's `composer.json`, or you can use composer as below:

```bash
composer require pelago/emogrifier
```

See https://getcomposer.org/ for more information and documentation.

## Usage

### Inlining Css

The most basic way to use the `CssInliner` class is to create an instance with
the original HTML, inline the external CSS, and then get back the resulting
HTML:

```php
use Pelago\Emogrifier\CssInliner;

…

$visualHtml = CssInliner::fromHtml($html)->inlineCss($css)->render();
```

If there is no external CSS file and all CSS is located within `<style>`
elements in the HTML, you can omit the `$css` parameter:

```php
$visualHtml = CssInliner::fromHtml($html)->inlineCss()->render();
```

If you would like to get back only the content of the `<body>` element instead of
the complete HTML document, you can use the `renderBodyContent` method instead:

```php
$bodyContent = $visualHtml = CssInliner::fromHtml($html)->inlineCss()
  ->renderBodyContent();
```

If you would like to modify the inlining process with any of the available
[options](#options), you will need to call the corresponding methods
before inlining the CSS. The code then would look like this:

```php
$visualHtml = CssInliner::fromHtml($html)->disableStyleBlocksParsing()
  ->inlineCss($css)->render();
```

There are also some other HTML-processing classes available
(all of which are subclasses of `AbstractHtmlProcessor`) which you can use
to further change the HTML after inlining the CSS.
(For more details on the classes, please have a look at the sections below.)
`CssInliner` and all HTML-processing classes can share the same `DOMDocument`
instance to work on:

```php
use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;

…

$cssInliner = CssInliner::fromHtml($html)->inlineCss($css);
$domDocument = $cssInliner->getDomDocument();
HtmlPruner::fromDomDocument($domDocument)->removeElementsWithDisplayNone()
  ->removeRedundantClassesAfterCssInlined($cssInliner);
$finalHtml = CssToAttributeConverter::fromDomDocument($domDocument)
  ->convertCssToVisualAttributes()->render();
```

### Normalizing and cleaning up HTML

The `HtmlNormalizer` class normalizes the given HTML in the following ways:

- add a document type (HTML5) if missing
- disentangle incorrectly nested tags
- add HEAD and BODY elements (if they are missing)
- reformat the HTML

The class can be used like this:

```php
use Pelago\Emogrifier\HtmlProcessor\HtmlNormalizer;

…

$cleanHtml = HtmlNormalizer::fromHtml($rawHtml)->render();
```

### Converting CSS styles to visual HTML attributes

The `CssToAttributeConverter` converts a few style attributes values to visual
HTML attributes. This allows to get at least a bit of visual styling for email
clients that do not support CSS well. For example, `style="width: 100px"`
will be converted to `width="100"`.

The class can be used like this:

```php
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;

…

$visualHtml = CssToAttributeConverter::fromHtml($rawHtml)
  ->convertCssToVisualAttributes()->render();
```

You can also have the `CssToAttributeConverter` work on a `DOMDocument`:

```php
$visualHtml = CssToAttributeConverter::fromDomDocument($domDocument)
  ->convertCssToVisualAttributes()->render();
```

### Removing redundant content and attributes from the HTML

The `HtmlPruner` class can reduce the size of the HTML by removing elements with
a `display: none` style declaration, and/or removing classes from `class`
attributes that are not required.

It can be used like this:

```php
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;

…

$prunedHtml = HtmlPruner::fromHtml($html)->removeElementsWithDisplayNone()
  ->removeRedundantClasses($classesToKeep)->render();
```

The `removeRedundantClasses` method accepts a whitelist of names of classes that
should be retained.  If this is a post-processing step after inlining CSS, you
can alternatively use `removeRedundantClassesAfterCssInlined`, passing it the
`CssInliner` instance that has inlined the CSS (and having the `HtmlPruner` work
on the `DOMDocument`).  This will use information from the `CssInliner` to
determine which classes are still required (namely, those used in uninlinable
rules that have been copied to a `<style>` element):

```php
$prunedHtml = HtmlPruner::fromDomDocument($cssInliner->getDomDocument())
  ->removeElementsWithDisplayNone()
  ->removeRedundantClassesAfterCssInlined($cssInliner)->render();
```

### Options

There are several options that you can set on the `CssInliner` instance before
calling the `inlineCss` method (or on the `Emogrifier` instance before calling
the `emogrify` method):

* `->disableStyleBlocksParsing()` - By default, `CssInliner` will grab
  all `<style>` blocks in the HTML and will apply the CSS styles as inline
  "style" attributes to the HTML. The `<style>` blocks will then be removed
  from the HTML. If you want to disable this functionality so that `CssInliner`
  leaves these `<style>` blocks in the HTML and does not parse them, you should
  use this option. If you use this option, the contents of the `<style>` blocks
  will _not_ be applied as inline styles and any CSS you want `CssInliner` to
  use must be passed in as described in the [Usage section](#usage) above.
* `->disableInlineStylesParsing()` - By default, `CssInliner`
  preserves all of the "style" attributes on tags in the HTML you pass to it.
  However if you want to discard all existing inline styles in the HTML before
  the CSS is applied, you should use this option.
* `->addAllowedMediaType(string $mediaName)` - By default, `CssInliner`
  will keep only media types `all`, `screen` and `print`. If you want to keep
  some others, you can use this method to define them.
* `->removeAllowedMediaType(string $mediaName)` - You can use this
  method to remove media types that Emogrifier keeps.
* `->addExcludedSelector(string $selector)` - Keeps elements from
  being affected by CSS inlining.

### Using the legacy Emogrifier class

In version 3.0.0, the `Emogrifier` class has been deprecated, and it will be
removed for version 4.0.0. Please update your code to use the new
`CssInliner` class instead.

If you are still using the deprecated class, here is how to use it:

First, you provide Emogrifier with the HTML and CSS you would like to merge.
This can happen directly during instantiation:

```php
$html = '<html><h1>Hello world!</h1></html>';
$css = 'h1 {font-size: 32px;}';
$emogrifier = new \Pelago\Emogrifier($html, $css);
```

You could also use the setters for providing this data after instantiation:

```php
$emogrifier = new \Pelago\Emogrifier();

$html = '<html><h1>Hello world!</h1></html>';
$css = 'h1 {font-size: 32px;}';

$emogrifier->setHtml($html);
$emogrifier->setCss($css);
```

After you have set the HTML and CSS, you can call the `emogrify` method to
merge both:

```php
$mergedHtml = $emogrifier->emogrify();
```

Emogrifier automatically adds a Content-Type meta tag to set the charset for
the document (if it is not provided).

If you would like to get back only the content of the BODY element instead of
the complete HTML document, you can use the `emogrifyBodyContent` instead:

```php
$bodyContent = $emogrifier->emogrifyBodyContent();
```

### Migrating from `Emogrifier` to `CssInliner`

#### Minimal example

Old code using `Emogrifier`:

```php
$emogrifier = new Emogrifier($html);
$html = $emogrifier->emogrify();
```

New code using `CssInliner`:

```php
$html = CssInliner::fromHtml($html)->inlineCss()->render();
```

NB: In this example, the old code removes elements with `display: none;`
while the new code does not, as the default behaviors of the old and
the new class differ in this regard.

#### More complex example

Old code using `Emogrifier`:

```php
$emogrifier = new Emogrifier($html, $css);
$emogrifier->enableCssToHtmlMapping();

$html = $emogrifier->emogrify();
```

New code using `CssInliner` and family:

```php
$domDocument = CssInliner::fromHtml($html)->inlineCss($css)->getDomDocument();

HtmlPruner::fromDomDocument($domDocument)->removeElementsWithDisplayNone(),
$html = CssToAttributeConverter::fromDomDocument($domDocument)
  ->convertCssToVisualAttributes()->render();
```

## Supported CSS selectors

Emogrifier currently supports the following
[CSS selectors](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors):

 * [type](https://developer.mozilla.org/en-US/docs/Web/CSS/Type_selectors)
 * [class](https://developer.mozilla.org/en-US/docs/Web/CSS/Class_selectors)
 * [ID](https://developer.mozilla.org/en-US/docs/Web/CSS/ID_selectors)
 * [universal](https://developer.mozilla.org/en-US/docs/Web/CSS/Universal_selectors):
 * [attribute](https://developer.mozilla.org/en-US/docs/Web/CSS/Attribute_selectors):
    * presence
    * exact value match
    * value with `~` (one word within a whitespace-separated list of words)
    * value with `|` (either exact value match or prefix followed by a hyphen)
    * value with `^` (prefix match)
    * value with `$` (suffix match)
    * value with `*` (substring match)
 * [adjacent](https://developer.mozilla.org/en-US/docs/Web/CSS/Adjacent_sibling_selectors)
 * [child](https://developer.mozilla.org/en-US/docs/Web/CSS/Child_selectors)
 * [descendant](https://developer.mozilla.org/en-US/docs/Web/CSS/Descendant_selectors)
 * [pseudo-classes](https://developer.mozilla.org/en-US/docs/Web/CSS/Pseudo-classes):
   * [first-child](https://developer.mozilla.org/en-US/docs/Web/CSS/:first-child)
   * [last-child](https://developer.mozilla.org/en-US/docs/Web/CSS/:last-child)
   * [not()](https://developer.mozilla.org/en-US/docs/Web/CSS/:not)

The following selectors are not implemented yet:

 * [universal](https://developer.mozilla.org/en-US/docs/Web/CSS/Universal_selectors)
 * [case-insensitive attribute value](https://developer.mozilla.org/en-US/docs/Web/CSS/Attribute_selectors#case-insensitive)
 * [general sibling](https://developer.mozilla.org/en-US/docs/Web/CSS/General_sibling_selectors)
 * [pseudo-classes](https://developer.mozilla.org/en-US/docs/Web/CSS/Pseudo-classes)
   (some of them will never be supported)
 * [pseudo-elements](https://developer.mozilla.org/en-US/docs/Web/CSS/Pseudo-elements)

## Caveats

* Emogrifier requires the HTML and the CSS to be UTF-8. Encodings like
  ISO8859-1 or ISO8859-15 are not supported.
* Emogrifier now preserves all valuable @media queries. Media queries
  can be very useful in responsive email design. See
  [media query support](https://litmus.com/help/email-clients/media-query-support/).
* Emogrifier will grab existing inline style attributes _and_ will
  grab `<style>` blocks from your HTML, but it will not grab CSS files
  referenced in <link> elements. (The problem email clients are going to ignore
  these tags anyway, so why leave them in your HTML?)
* Even with styles inline, certain CSS properties are ignored by certain email
  clients. For more information, refer to these resources:
    * [http://www.email-standards.org/](http://www.email-standards.org/)
    * [https://www.campaignmonitor.com/css/](https://www.campaignmonitor.com/css/)
    * [http://templates.mailchimp.com/resources/email-client-css-support/](http://templates.mailchimp.com/resources/email-client-css-support/)
* All CSS attributes that apply to an element will be applied, even if they are
  redundant. For example, if you define a font attribute _and_ a font-size
  attribute, both attributes will be applied to that element (in other words,
  the more specific attribute will not be combined into the more general
  attribute).
* There's a good chance you might encounter problems if your HTML is not
  well-formed and valid (DOMDocument might complain). If you get problems like
  this, consider running your HTML through
  [Tidy](http://php.net/manual/en/book.tidy.php) before you pass it to
  Emogrifier.
* Emogrifier automatically converts the provided (X)HTML into HTML5, i.e.,
  self-closing tags will lose their slash. To keep your HTML valid, it is
  recommended to use HTML5 instead of one of the XHTML variants.
* Emogrifier only supports CSS1 level selectors and a few CSS2 level selectors
  (but not all of them). It does not support pseudo selectors. (Emogrifier
  works by converting CSS selectors to XPath selectors, and pseudo selectors
  cannot be converted accurately).

## Steps to release a new version

1. Create a pull request "Prepare release of version x.y.z" with the following
   changes.
1. In the [composer.json](composer.json), update the `branch-alias` entry to
   point to the release _after_ the upcoming release.
1. In the [README.md](README.md), update the version numbers in the section
   [Installing with Composer](#installing-with-composer).
1. In the [CHANGELOG.md](CHANGELOG.md), set the version number and remove any
   empty sections.
1. Have the pull request reviewed and merged.
1. In the [Releases tab](https://github.com/MyIntervals/emogrifier/releases),
   create a new release and copy the change log entries to the new release.
1. Post about the new release on social media.

## Maintainers

* [Oliver Klee](https://github.com/oliverklee)
* [Zoli Szabó](https://github.com/zoliszabo)
* [Jake Hotson](https://github.com/JakeQZ)
* [John Reeve](https://github.com/jjriv)
