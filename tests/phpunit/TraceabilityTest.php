<?php

namespace WikiTraceability\Tests;

use PHPUnit\Framework\TestCase;
use WikiTraceability\Traceability;

/**
 * @covers WikiTraceability\Traceability
 */
class TraceabilityTest extends TestCase
{
    public function testInitDoesNotThrow()
    {
        $this->assertNull( Traceability::init() );
    }

    public function testMainPageMessagePointsToProductSearch()
    {
        $message = null;
        Traceability::onMessagesPreLoad( 'Mainpage', $message, 'en' );

        $this->assertSame( 'Special:TraceabilityProduct', $message );
    }

    public function testRenderTraceabilityTree()
    {
        $productName = 'TestProduct';
        $html = Traceability::renderTraceabilityTree( $productName );

        $this->assertStringContainsString( 'wikitraceability-tree-container', $html );
        $this->assertStringContainsString( 'data-product="TestProduct"', $html );
        $this->assertStringContainsString( 'WikiTraceability.initTree', $html );
    }

    public function testRenderTraceabilityTreeEscapesHtml()
    {
        $productName = '<script>alert("xss")</script>';
        $html = Traceability::renderTraceabilityTree( $productName );

        $this->assertStringContainsString( 'htmlspecialchars', $html );
        $this->assertStringNotContainsString( '<script>', $html );
    }

    public function testGetProductTransformationsReturnsEmptyArrayWithoutSMW()
    {
        $transformations = Traceability::getProductTransformations( 'TestProduct' );
        $this->assertIsArray( $transformations );
    }

    public function testGetTransformationDetailsReturnsEmptyArrayWithoutSMW()
    {
        $details = Traceability::getTransformationDetails( 'TestTransformation' );
        $this->assertIsArray( $details );
    }

    public function testRenderTraceabilityTreeWithEmptyProduct()
    {
        $html = Traceability::renderTraceabilityTree( '' );

        $this->assertStringContainsString( 'wikitraceability-tree-container', $html );
        $this->assertStringContainsString( 'data-product=""', $html );
    }

    public function testRenderTraceabilityTreeWithSpecialCharacters()
    {
        $productName = 'Test&Product<>"';
        $html = Traceability::renderTraceabilityTree( $productName );

        $this->assertStringContainsString( 'data-product=', $html );
        $this->assertStringContainsString( 'WikiTraceability.initTree', $html );
    }
}
