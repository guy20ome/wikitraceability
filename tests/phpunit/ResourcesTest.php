<?php

namespace WikiTraceability\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class ResourcesTest extends TestCase
{
    public function testTraceabilityJsExists()
    {
        $path = __DIR__ . '/../../resources/src/Traceability.js';
        $this->assertFileExists( $path );
    }

    public function testTraceabilityJsIsNotEmpty()
    {
        $path = __DIR__ . '/../../resources/src/Traceability.js';
        $content = file_get_contents( $path );
        $this->assertNotEmpty( $content );
    }

    public function testTraceabilityJsHasWikiTraceabilityNamespace()
    {
        $path = __DIR__ . '/../../resources/src/Traceability.js';
        $content = file_get_contents( $path );
        
        $this->assertStringContainsString( 'WikiTraceability', $content );
    }

    public function testTraceabilityJsHasTreeFunctionality()
    {
        $path = __DIR__ . '/../../resources/src/Traceability.js';
        $content = file_get_contents( $path );

        $this->assertStringContainsString( 'TraceabilityTree', $content );
        $this->assertStringContainsString( 'initTree', $content );
    }

    public function testTraceabilityJsHasApiCalls()
    {
        $path = __DIR__ . '/../../resources/src/Traceability.js';
        $content = file_get_contents( $path );

        $this->assertStringContainsString( 'mw.Api', $content );
        $this->assertStringContainsString( 'action', $content );
        $this->assertStringContainsString( 'ask', $content );
    }

    public function testProductLookupUsesOpenFoodFactsApi()
    {
        $script = file_get_contents( __DIR__ . '/../../resources/src/Traceability.js' );
        $productForm = file_get_contents( __DIR__ . '/../../includes/SpecialProduct.php' );

        $this->assertStringContainsString( 'wikitraceabilityopenfoodfacts', $script );
        $this->assertStringContainsString( 'wpProductBarcode', $script );
        $this->assertStringContainsString( 'Open Food Facts', $productForm );
        $this->assertStringContainsString( 'ODbL', $productForm );
    }

    public function testProductLookupSearchesTheLocalWikiCatalog()
    {
        $script = file_get_contents( __DIR__ . '/../../resources/src/Traceability.js' );
        $productForm = file_get_contents( __DIR__ . '/../../includes/SpecialProduct.php' );

        $this->assertStringContainsString( 'initLocalProductLookup', $script );
        $this->assertStringContainsString( "action: 'ask'", $script );
        $this->assertStringContainsString( '[[ProductName::+]]', $script );
        $this->assertStringContainsString( 'productName.toLowerCase()', $script );
        $this->assertStringContainsString( 'wikitraceability-local-results', $script );
        $this->assertStringContainsString( 'wikitraceability-local-search', $productForm );
    }

    public function testTreeNodesLinkToTheirCorrespondingPages()
    {
        $path = __DIR__ . '/../../resources/src/Traceability.js';
        $content = file_get_contents( $path );

        $this->assertStringContainsString( "'Special:TraceabilityProduct', { product: product.name }", $content );
        $this->assertStringContainsString( "getAttribute( 'data-can-edit' ) === 'true'", $content );
        $this->assertStringContainsString( 'mw.util.getUrl( product.name )', $content );
        $this->assertStringContainsString( 'pageTitle: pageTitle', $content );
        $this->assertStringContainsString( 'mw.util.getUrl( transformation.pageTitle )', $content );
    }

    public function testEmptyTraceabilityTreeLinksToTransformationCreation()
    {
        $script = file_get_contents( __DIR__ . '/../../resources/src/Traceability.js' );

        $this->assertStringContainsString( 'No transformations found. Add a transformation for this product.', $script );
        $this->assertStringContainsString( "'Special:TraceabilityTransformation'", $script );
        $this->assertStringContainsString( 'wpOutputProducts: treeData.name', $script );
    }

    public function testTransformationProductSelectorsUseThreeCharacterCatalogSuggestions()
    {
        $script = file_get_contents( __DIR__ . '/../../resources/src/Traceability.js' );
        $form = file_get_contents( __DIR__ . '/../../includes/SpecialTransformation.php' );

        $this->assertStringContainsString( 'initTransformationProductSelectors', $script );
        $this->assertStringContainsString( 'term.length < 3', $script );
        $this->assertStringContainsString( '[[ProductName::+]]|?ProductName|limit=500', $script );
        $this->assertStringContainsString( 'wikitraceability-product-suggestion', $script );
        $this->assertStringContainsString( 'aria-activedescendant', $script );
        $this->assertStringContainsString( 'data-add-product', $script );
        $this->assertStringContainsString( 'data-remove-product', $script );
        $this->assertStringContainsString( 'length > 1', $script );
        $this->assertStringContainsString( 'productRows.length === 1', $script );
        $this->assertStringContainsString( 'getArray( \'wpInputProducts\' )', $form );
        $this->assertStringContainsString( 'getArray( \'wpOutputProducts\' )', $form );
    }

    public function testTraceabilityCssExists()
    {
        $path = __DIR__ . '/../../resources/styles/Traceability.css';
        $this->assertFileExists( $path );
    }

    public function testTraceabilityCssIsNotEmpty()
    {
        $path = __DIR__ . '/../../resources/styles/Traceability.css';
        $content = file_get_contents( $path );
        $this->assertNotEmpty( $content );
    }

    public function testTraceabilityCssHasTreeStyles()
    {
        $path = __DIR__ . '/../../resources/styles/Traceability.css';
        $content = file_get_contents( $path );

        $this->assertStringContainsString( '.traceability-tree', $content );
        $this->assertStringContainsString( '.traceability-node', $content );
    }

    public function testTraceabilityCssHasFormStyles()
    {
        $path = __DIR__ . '/../../resources/styles/Traceability.css';
        $content = file_get_contents( $path );

        $this->assertStringContainsString( '.mw-htmlform', $content );
    }

    public function testTraceabilityCssHasProductAndTransformationStyles()
    {
        $path = __DIR__ . '/../../resources/styles/Traceability.css';
        $content = file_get_contents( $path );

        $this->assertStringContainsString( '.traceability-product', $content );
        $this->assertStringContainsString( '.traceability-transformation', $content );
    }

    public function testTransformationInputsAreDisplayedSideBySide()
    {
        $script = file_get_contents( __DIR__ . '/../../resources/src/Traceability.js' );
        $styles = file_get_contents( __DIR__ . '/../../resources/styles/Traceability.css' );

        $this->assertStringContainsString( 'traceability-children traceability-inputs', $script );
        $this->assertStringContainsString( 'traceability-product-branch', $script );
        $this->assertMatchesRegularExpression( '/\.traceability-inputs\s*\{[^}]*display:\s*flex/s', $styles );
        $this->assertMatchesRegularExpression( '/\.traceability-inputs\s*\{[^}]*flex-wrap:\s*wrap/s', $styles );
    }

    public function testTraceabilityCssHasSuccessAndErrorStyles()
    {
        $path = __DIR__ . '/../../resources/styles/Traceability.css';
        $content = file_get_contents( $path );

        $this->assertStringContainsString( '.success', $content );
        $this->assertStringContainsString( '.error', $content );
    }
}
