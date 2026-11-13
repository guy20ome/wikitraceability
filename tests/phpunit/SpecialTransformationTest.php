<?php

namespace WikiTraceability\Tests;

use PHPUnit\Framework\TestCase;
use WikiTraceability\SpecialTransformation;

/**
 * @covers WikiTraceability\SpecialTransformation
 */
class SpecialTransformationTest extends TestCase
{
    private $specialPage;
    private $outputMock;
    private $requestMock;
    private $userMock;
    private $prefilledOutputProduct = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputMock = $this->createMock( \stdClass::class );
        $this->outputMock->method( 'addWikiTextAsInterface' )->willReturn( null );
        $this->outputMock->method( 'addHTML' )->willReturn( null );

        $this->requestMock = $this->getMockBuilder( \WebRequest::class )
            ->disableOriginalConstructor()
            ->onlyMethods( [ 'getText', 'getArray', 'getVal', 'wasPosted' ] )
            ->getMock();
        $this->requestMock->method( 'getText' )->willReturnCallback( function ( $key ) {
            switch ( $key ) {
                case 'wpAction': return null;
                case 'wpTransformationName': return '';
                case 'wpOutputProducts': return $this->prefilledOutputProduct;
                default: return null;
            }
        } );
        $this->requestMock->method( 'getArray' )->willReturn( [] );
        $this->requestMock->method( 'wasPosted' )->willReturn( false );

        $this->userMock = $this->createMock( \stdClass::class );
        $this->userMock->method( 'isRegistered' )->willReturn( true );

        $this->specialPage = $this->getMockBuilder( SpecialTransformation::class )
            ->onlyMethods( [ 'getOutput', 'getRequest', 'getUser', 'setHeaders' ] )
            ->getMock();

        $this->specialPage->method( 'getOutput' )->willReturn( $this->outputMock );
        $this->specialPage->method( 'getRequest' )->willReturn( $this->requestMock );
        $this->specialPage->method( 'getUser' )->willReturn( $this->userMock );
        $this->specialPage->method( 'setHeaders' )->willReturn( null );
    }

    public function testConstructor()
    {
        $specialPage = new SpecialTransformation();
        $this->assertInstanceOf( SpecialTransformation::class, $specialPage );
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

    public function testDisplayTransformationFormContainsRequiredFields()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( 'Transformation Name', $html );
        $this->assertStringContainsString( 'wpTransformationName', $html );
        $this->assertStringContainsString( 'Transformation Type', $html );
        $this->assertStringContainsString( 'wpTransformationType', $html );
        $this->assertStringContainsString( 'Input Products', $html );
        $this->assertStringContainsString( 'wpInputProducts', $html );
        $this->assertStringContainsString( 'Output Products', $html );
        $this->assertStringContainsString( 'wpOutputProducts', $html );
        $this->assertStringContainsString( 'Input Quantity per Unit', $html );
        $this->assertStringContainsString( 'wpInputQuantityPerUnit', $html );
        $this->assertStringContainsString( 'Output Quantity per Unit', $html );
        $this->assertStringContainsString( 'wpOutputQuantityPerUnit', $html );
        $this->assertStringContainsString( 'Description', $html );
        $this->assertStringContainsString( 'wpTransformationDescription', $html );
        $this->assertStringContainsString( 'Create Transformation', $html );
    }

    public function testFormHasTransformationTypeOptions()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( '<option value="processing">Processing</option>', $html );
        $this->assertStringContainsString( '<option value="mixing">Mixing</option>', $html );
        $this->assertStringContainsString( '<option value="assembly">Assembly</option>', $html );
        $this->assertStringContainsString( '<option value="refining">Refining</option>', $html );
        $this->assertStringContainsString( '<option value="packaging">Packaging</option>', $html );
    }

    public function testTransformationNameIsRequired()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( 'required', $html );
        $this->assertStringContainsString( 'wpTransformationName', $html );
    }

    public function testInputAndOutputProductsAreRequired()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( 'required', $html );
        $this->assertStringContainsString( 'wpInputProducts', $html );
        $this->assertStringContainsString( 'required', $html );
        $this->assertStringContainsString( 'wpOutputProducts', $html );
    }

    public function testQuantityFieldsAcceptDecimal()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( 'type="number"', $html );
        $this->assertStringContainsString( 'step="any"', $html );
        $this->assertStringContainsString( 'wpInputQuantityPerUnit', $html );
        $this->assertStringContainsString( 'wpOutputQuantityPerUnit', $html );
    }

    public function testProductFieldsStartWithOneInputAndOutputAndCanBeAdded()
    {
        $html = '';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertSame( 1, substr_count( $html, 'name="wpInputProducts[]"' ) );
        $this->assertSame( 1, substr_count( $html, 'name="wpOutputProducts[]"' ) );
        $this->assertStringContainsString( 'data-add-product="input"', $html );
        $this->assertStringContainsString( 'data-add-product="output"', $html );
        $this->assertSame( 2, substr_count( $html, 'data-remove-product' ) );
        $this->assertSame( 2, substr_count( $html, 'data-remove-product aria-label=' ) );
        $this->assertSame( 2, substr_count( $html, ' disabled' ) );
        $this->assertStringContainsString( 'role="listbox"', $html );
        $this->assertStringContainsString( 'aria-autocomplete="list"', $html );
    }

    public function testOutputProductCanBePrefilledFromRequest()
    {
        $html = '';
        $this->prefilledOutputProduct = 'Wheat';
        $this->outputMock->method( 'addHTML' )->will( $this->returnCallback( function ( $content ) use ( &$html ) {
            $html = $content;
        } ) );

        $this->specialPage->execute( '' );

        $this->assertStringContainsString( 'name="wpOutputProducts[]"', $html );
        $this->assertStringContainsString( 'value="Wheat"', $html );
    }
}
