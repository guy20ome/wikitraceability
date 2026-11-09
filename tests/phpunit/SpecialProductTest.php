<?php

namespace WikiTraceability\Tests;

use PHPUnit\Framework\TestCase;
use WikiTraceability\SpecialProduct;

/**
 * @covers WikiTraceability\SpecialProduct
 */
class SpecialProductTest extends TestCase
{
    private $specialPage;
    private $outputMock;
    private $requestMock;
    private $userMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputMock = $this->createMock( \stdClass::class );
        $this->outputMock->method( 'addWikiTextAsInterface' )->willReturn( null );
        $this->outputMock->method( 'addHTML' )->willReturn( null );

        $this->requestMock = $this->createMock( \stdClass::class );
        $this->requestMock->method( 'getText' )->willReturnCallback( function ( $key ) {
            switch ( $key ) {
                case 'wpAction': return null;
                case 'wpProductName': return '';
                default: return null;
            }
        } );

        $this->userMock = $this->createMock( \stdClass::class );
        $this->userMock->method( 'isRegistered' )->willReturn( true );

        // Create a mock of SpecialProduct that we can control
        $this->specialPage = $this->getMockBuilder( SpecialProduct::class )
            ->onlyMethods( [ 'getOutput', 'getRequest', 'getUser', 'setHeaders' ] )
            ->getMock();

        $this->specialPage->method( 'getOutput' )->willReturn( $this->outputMock );
        $this->specialPage->method( 'getRequest' )->willReturn( $this->requestMock );
        $this->specialPage->method( 'getUser' )->willReturn( $this->userMock );
        $this->specialPage->method( 'setHeaders' )->willReturn( null );
    }

    public function testConstructor()
    {
        $specialPage = new SpecialProduct();
        $this->assertInstanceOf( SpecialProduct::class, $specialPage );
    }

    public function testExecuteDisplaysForm()
    {
        $this->outputMock->expects( $this->once() )
            ->method( 'addWikiTextAsInterface' );

        $this->outputMock->expects( $this->once() )
            ->method( 'addHTML' )
            ->with( $this->stringContains( '<form' ) );

        $this->specialPage->execute( '' );
    }

    public function testDisplayProductFormContainsRequiredFields()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( 'Product Name', $html );
        $this->assertStringContainsString( 'wpProductName', $html );
        $this->assertStringContainsString( 'Weight', $html );
        $this->assertStringContainsString( 'wpProductWeight', $html );
        $this->assertStringContainsString( 'Weight Unit', $html );
        $this->assertStringContainsString( 'wpProductWeightUnit', $html );
        $this->assertStringContainsString( 'Description', $html );
        $this->assertStringContainsString( 'wpProductDescription', $html );
        $this->assertStringContainsString( 'Price', $html );
        $this->assertStringContainsString( 'wpProductPrice', $html );
        $this->assertStringContainsString( 'Category', $html );
        $this->assertStringContainsString( 'wpProductCategory', $html );
        $this->assertStringContainsString( 'Create Product', $html );
    }

    public function testFormHasWeightUnitOptions()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( '<option value="kg">kg</option>', $html );
        $this->assertStringContainsString( '<option value="g">g</option>', $html );
        $this->assertStringContainsString( '<option value="lbs">lbs</option>', $html );
        $this->assertStringContainsString( '<option value="oz">oz</option>', $html );
    }

    public function testProductNameIsRequired()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( 'required', $html );
        $this->assertStringContainsString( 'wpProductName', $html );
    }

    public function testWeightFieldAcceptsDecimal()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( 'type="number"', $html );
        $this->assertStringContainsString( 'step="any"', $html );
    }

    public function testPriceFieldAcceptsDecimal()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( 'wpProductPrice', $html );
        $this->assertStringContainsString( 'step="0.01"', $html );
    }
}
