<?php

namespace Pelago\Tests\Unit\Emogrifier\Support\Tests\Fixtures;

use Pelago\Tests\Unit\Emogrifier\Support\AssertCss;

/**
 * Mock test case for testing `AssertCss`.
 */
class TestingAssertCss extends \PHPUnit_Framework_TestCase
{
    use AssertCss
    {
        getCssNeedleRegExp as public;
        assertContainsCss as public;
        assertNotContainsCss as public;
        assertContainsCssCount as public;
    }
}
