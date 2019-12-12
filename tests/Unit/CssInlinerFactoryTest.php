<?php

declare(strict_types=1);

namespace Pelago\Emogrifer\Tests\Unit;

use DOMDocument;
use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\CssInlinerFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test case.
 *
 * @covers \Pelago\Emogrifier\CssInlinerFactory
 *
 * @author SpazzMarticus <SpazzMarticus@users.noreply.github.com>
 */
class CssInlinerFactoryTest extends TestCase
{
    /**
     * @var CssInlinerFactory
     */
    protected $factory;

    /**
     * Setup factory for tests
     */
    protected function setUp()
    {
        parent::setUp();
        $this->factory = new CssInlinerFactory();
    }

    /**
     * @test
     */
    public function createsInstanceFromHtmlString()
    {
        $cssInliner = $this->factory->createFromHtml(CssInlinerTest::COMMON_TEST_HTML);
        self::assertInstanceOf(CssInliner::class, $cssInliner);
    }

    /**
     * @test
     */
    public function createsInstanceFromDomDocument()
    {
        $document = new DOMDocument();
        $document->loadHTML(CssInlinerTest::COMMON_TEST_HTML);

        $cssInliner = $this->factory->createFromDomDocument($document);

        self::assertInstanceOf(CssInliner::class, $cssInliner);
    }
}
