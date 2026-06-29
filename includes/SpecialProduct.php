<?php

namespace WikiTraceability;

use SpecialPage;
use WikiPage;
use WikitextContent;
use Title;
use Hooks;

class SpecialProduct extends SpecialPage {
    public function __construct() {
        parent::__construct( 'TraceabilityProduct' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $out = $this->getOutput();

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
        $form = '<form method="post" class="mw-htmlform">'
            . '<input type="hidden" name="wpAction" value="create" />'
            . '<label for="wpProductName">Product Name</label><br />'
            . '<input id="wpProductName" name="wpProductName" type="text" class="mw-htmlform-field" required /><br />'
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
        $dbr = wfGetDB( DB_REPLICA );
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
        $description = $request->getText( 'wpProductDescription' );
        $price = $request->getText( 'wpProductPrice' );
        $category = $request->getText( 'wpProductCategory' );

        // Build SMW semantic data
        $semanticContent = "{{Product Definition\n";
        $semanticContent .= "| ProductName = $productName\n";
        if ( $weight ) $semanticContent .= "| ProductWeight = $weight\n";
        if ( $weightUnit ) $semanticContent .= "| ProductWeightUnit = $weightUnit\n";
        if ( $description ) $semanticContent .= "| ProductDescription = $description\n";
        if ( $price ) $semanticContent .= "| ProductPrice = $price\n";
        if ( $category ) $semanticContent .= "| ProductCategory = $category\n";
        $semanticContent .= "}}\n";

        // Create or update the page
        $wikiPage = new WikiPage( $title );
        $content = new WikitextContent( $semanticContent );
        $status = $wikiPage->doEditContent( $content, 'Created via TraceabilityProduct special page' );

        if ( $status->isGood() ) {
            $out->addHTML( '<p class="success">Product <strong>' . htmlspecialchars( $productName ) . '</strong> created with SMW properties.</p>' );
            $out->addHTML( '<p><a href="' . $title->getLocalURL() . '">View product page</a></p>' );
        } else {
            $out->addHTML( '<p class="error">Failed to create product: ' . $status->getMessage()->text() . '</p>' );
        }
    }
}
