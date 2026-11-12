<?php

namespace WikiTraceability;

use SpecialPage;
use WikiPage;
use WikitextContent;
use Title;

class SpecialProduct extends SpecialPage {
    public function __construct() {
        parent::__construct( 'TraceabilityProduct' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $out = $this->getOutput();
        $out->addModules( 'ext.wikitraceability' );

        $out->addWikiTextAsInterface( '== Product entry ==\nCreate or edit traceability products with Semantic MediaWiki integration.' );

        // Handle form submission
        $request = $this->getRequest();
        $action = $request->getText( 'wpAction' );

        if ( $action === 'create' && $this->getUser()->isRegistered() ) {
            $this->handleProductCreation( $request, $out );
        }

        // Display product entry form
        $this->displayProductForm( $out );
    }

    private function displayProductForm( $out ) {
        $form = '<section class="wikitraceability-off-lookup" aria-labelledby="off-lookup-heading">'
            . '<h2 id="off-lookup-heading">Find an existing food product</h2>'
            . '<p>Search Open Food Facts by product name or barcode. Search only runs when you submit this form.</p>'
            . '<form id="wikitraceability-off-search">'
            . '<label for="wikitraceability-off-search-term">Product name or barcode</label><br />'
            . '<input id="wikitraceability-off-search-term" type="search" class="mw-htmlform-field" minlength="2" maxlength="100" required /> '
            . '<button type="submit" class="mw-htmlform-submit">Search Open Food Facts</button>'
            . '</form>'
            . '<p class="wikitraceability-off-attribution">Product data from '
            . '<a href="https://world.openfoodfacts.org/" target="_blank" rel="noopener noreferrer">Open Food Facts</a>, '
            . 'available under the <a href="https://opendatacommons.org/licenses/odbl/1-0/" target="_blank" rel="noopener noreferrer">Open Database License (ODbL)</a>. '
            . 'Data is community-contributed; please verify it.</p>'
            . '<div id="wikitraceability-off-results" aria-live="polite"></div>'
            . '</section>'
            . '<form method="post" class="mw-htmlform">'
            . '<input type="hidden" name="wpAction" value="create" />'
            . '<input id="wpProductSource" name="wpProductSource" type="hidden" value="" />'
            . '<label for="wpProductName">Product Name</label><br />'
            . '<input id="wpProductName" name="wpProductName" type="text" class="mw-htmlform-field" required /><br />'
            . '<label for="wpProductBrand">Brand</label><br />'
            . '<input id="wpProductBrand" name="wpProductBrand" type="text" class="mw-htmlform-field" /><br />'
            . '<label for="wpProductBarcode">Barcode</label><br />'
            . '<input id="wpProductBarcode" name="wpProductBarcode" type="text" class="mw-htmlform-field" /><br />'
            . '<label for="wpProductWeight">Weight</label><br />'
            . '<input id="wpProductWeight" name="wpProductWeight" type="number" step="any" class="mw-htmlform-field" /><br />'
            . '<label for="wpProductWeightUnit">Weight Unit</label><br />'
            . '<select id="wpProductWeightUnit" name="wpProductWeightUnit" class="mw-htmlform-field">'
            . '<option value="kg">kg</option><option value="g">g</option><option value="lbs">lbs</option><option value="oz">oz</option>'
            . '</select><br />'
            . '<label for="wpProductDescription">Description</label><br />'
            . '<textarea id="wpProductDescription" name="wpProductDescription" class="mw-htmlform-field"></textarea><br />'
            . '<label for="wpProductPrice">Price (USD)</label><br />'
            . '<input id="wpProductPrice" name="wpProductPrice" type="number" step="0.01" class="mw-htmlform-field" /><br />'
            . '<label for="wpProductCategory">Category</label><br />'
            . '<input id="wpProductCategory" name="wpProductCategory" type="text" class="mw-htmlform-field" /><br />'
            . '<input type="submit" value="Create Product" class="mw-htmlform-submit" />'
            . '</form>';

        $out->addHTML( $form );
    }

    private function handleProductCreation( $request, $out ) {
        $productName = trim( $request->getText( 'wpProductName' ) );

        if ( !$productName ) {
            $out->addHTML( '<p class="error">Product name is required.</p>' );
            return;
        }

        // Find existing product or create new page
        $title = Title::newFromText( $productName, NS_MAIN );
        if ( !$title ) {
            $out->addHTML( '<p class="error">Invalid product name.</p>' );
            return;
        }

        $weight = $request->getText( 'wpProductWeight' );
        $weightUnit = $request->getText( 'wpProductWeightUnit' );
        $brand = $request->getText( 'wpProductBrand' );
        $barcode = $request->getText( 'wpProductBarcode' );
        $source = $request->getText( 'wpProductSource' );
        $description = $request->getText( 'wpProductDescription' );
        $price = $request->getText( 'wpProductPrice' );
        $category = $request->getText( 'wpProductCategory' );

        $properties = [
            'ProductName' => $productName,
            'ProductBrand' => $brand,
            'ProductBarcode' => $barcode,
            'ProductWeight' => $weight,
            'ProductWeightUnit' => $weightUnit,
            'ProductDescription' => $description,
            'ProductPrice' => $price,
            'ProductCategory' => $category
        ];
        if ( $source === 'Open Food Facts' ) {
            $properties['ProductSource'] = 'Open Food Facts';
            $properties['ProductDataLicense'] = 'ODbL';
            if ( $barcode !== '' ) {
                $properties['ProductSourceURL'] = 'https://world.openfoodfacts.org/product/' . rawurlencode( $barcode );
            }
        }
        $semanticContent = '';
        foreach ( $properties as $property => $value ) {
            if ( $value !== '' ) {
                $semanticContent .= '[[' . $property . '::' . self::escapeSemanticValue( $value ) . "]]\n";
            }
        }
        if ( $source === 'Open Food Facts' ) {
            $sourceLink = $barcode !== ''
                ? ' [https://world.openfoodfacts.org/product/' . rawurlencode( $barcode ) . ' Open Food Facts]'
                : ' Open Food Facts';
            $semanticContent .= 'Product data sourced from' . $sourceLink . " (ODbL).\n";
        }

        // Create or update the page
        $wikiPage = new WikiPage( $title );
        $content = new WikitextContent( $semanticContent );
        $status = $wikiPage->doUserEditContent(
            $content,
            $this->getAuthority(),
            'Created via TraceabilityProduct special page'
        );

        if ( $status->isGood() ) {
            $out->addHTML( '<p class="success">Product <strong>' . htmlspecialchars( $productName ) . '</strong> created with SMW properties.</p>' );
            $out->addHTML( '<p><a href="' . $title->getLocalURL() . '">View product page</a></p>' );
        } else {
            $out->addHTML( '<p class="error">Failed to create product: ' . $status->getMessage()->text() . '</p>' );
        }
    }

    private static function escapeSemanticValue( string $value ): string {
        return strtr( $value, [
            '&' => '&amp;',
            '[' => '&#91;',
            ']' => '&#93;',
            '|' => '&#124;',
            '{' => '&#123;',
            '}' => '&#125;',
            "\r" => ' ',
            "\n" => ' '
        ] );
    }
}
