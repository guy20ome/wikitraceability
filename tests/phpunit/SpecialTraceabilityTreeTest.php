<?php

namespace WikiTraceability\Tests;

use PHPUnit\Framework\TestCase;
use WikiTraceability\SpecialTraceabilityTree;

/**
 * @covers WikiTraceability\SpecialTraceabilityTree
 */
class SpecialTraceabilityTreeTest extends TestCase
{
    private $specialPage;
    private $outputMock;
    private $requestMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputMock = $this->createMock( \stdClass::class );
        $this->outputMock->method( 'addWikiTextAsInterface' )->willReturn( null );
        $this->outputMock->method( 'addHTML' )->willReturn( null );

        $this->requestMock = $this->createMock( \stdClass::class );
        $this->requestMock->method( 'getText' )->willReturn( '' );

        $this->specialPage = $this->getMockBuilder( SpecialTraceabilityTree::class )
            ->onlyMethods( [ 'getOutput', 'getRequest', 'setHeaders' ] )
            ->getMock();

        $this->specialPage->method( 'getOutput' )->willReturn( $this->outputMock );
        $this->specialPage->method( 'getRequest' )->willReturn( $this->requestMock );
        $this->specialPage->method( 'setHeaders' )->willReturn( null );
    }

    public function testConstructor()
    {
        $specialPage = new SpecialTraceabilityTree();
        $this->assertInstanceOf( SpecialTraceabilityTree::class, $specialPage );
    }

    public function testExecuteDisplaysTitle()
    {
        $this->outputMock->expects( $this->once() )
            ->method( 'addWikiTextAsInterface' )
            ->with( $this->stringContains( 'Traceability Tree' ) );

        $this->specialPage->execute( '' );
    }

    public function testExecuteDisplaysProductSelector()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html .= $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( 'Select a Product', $html );
        $this->assertStringContainsString( '<form', $html );
        $this->assertStringContainsString( 'product', $html );
        $this->assertStringContainsString( 'View Tree', $html );
    }

    public function testExecuteWithProductDisplaysTree()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html .= $content;
        } ) );

        $this->specialPage->execute( 'TestProduct' );

        $this->assertStringContainsString( 'Traceability Tree for: TestProduct', $html );
        $this->assertStringContainsString( 'wikitraceability-tree-container', $html );
        $this->assertStringContainsString( 'data-product="TestProduct"', $html );
    }

    public function testExecuteWithProductInRequestDisplaysTree()
    {
        $this->requestMock->method( 'getText' )->willReturnCallback( function ( $key ) {
            return $key === 'product' ? 'RequestProduct' : '';
        } );

        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html .= $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( 'Traceability Tree for: RequestProduct', $html );
        $this->assertStringContainsString( 'data-product="RequestProduct"', $html );
    }

    public function testDisplayTreeContainsContainer()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html .= $content;
        } ) );

        $this->specialPage->execute( 'TestProduct' );

        $this->assertStringContainsString( 'id="traceability-tree-container"', $html );
        $this->assertStringContainsString( 'class="wikitraceability-tree-container"', $html );
    }

    public function testDisplayTreeContainsJavaScript()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html .= $content;
        } ) );

        $this->specialPage->execute( 'TestProduct' );

        $this->assertStringContainsString( '<script>', $html );
        $this->assertStringContainsString( 'WikiTraceability.initTree', $html );
    }

    public function testDisplayTreeContainsDetailsContainer()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html .= $content;
        } ) );

        $this->specialPage->execute( 'TestProduct' );

        $this->assertStringContainsString( 'traceability-tree-details', $html );
    }

    public function testDisplayProductInfoShowsProductLink()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html .= $content;
        } ) );

        $this->specialPage->execute( 'TestProduct' );

        $this->assertStringContainsString( 'View Product Page', $html );
        $this->assertStringContainsString( '/wiki/TestProduct', $html );
    }

    public function testGetPageTitleReturnsCorrectTitle()
    {
        $specialPage = new SpecialTraceabilityTree();
        $title = $specialPage->getPageTitle();

        $this->assertInstanceOf( \stdClass::class, $title );
    }
}
