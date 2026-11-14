<?php

namespace WikiTraceability;

use SpecialPage;
use Title;

class SpecialTraceabilityTree extends SpecialPage {
    public function __construct() {
        parent::__construct( 'TraceabilityTree' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $out = $this->getOutput();
        $request = $this->getRequest();

        $out->addWikiTextAsInterface( '== Traceability Tree ==\nView the traceability tree for a product.' );

        $productName = trim( (string)$subPage );
        if ( !$productName ) {
            $productName = $request->getText( 'product' );
        }

        if ( $productName ) {
            $out->addModules( 'ext.wikitraceability' );
            $this->displayTree( $out, $productName );
        }

        $this->displayProductSelector( $out );
    }

    private function displayProductSelector( $out ) {
        $form = '<form method="get" class="mw-htmlform" action="' . $this->getPageTitle()->getLocalURL() . '">'
            . '<input type="hidden" name="title" value="' . $this->getPageTitle()->getPrefixedText() . '" />'
            . '<label for="product">Product Name</label><br />'
            . '<input id="product" name="product" type="text" class="mw-htmlform-field" required /><br />'
            . '<input type="submit" value="View Tree" class="mw-htmlform-submit" />'
            . '</form>';

        $out->addHTML( '<h3>Select a Product</h3>' );
        $out->addHTML( $form );
    }

    private function displayTree( $out, $productName ) {
        $out->addHTML( '<h2>Traceability Tree for: ' . htmlspecialchars( $productName ) . '</h2>' );
        
        $out->addHTML(
            '<div id="traceability-tree-container" class="wikitraceability-tree-container" data-product="' .
            htmlspecialchars( $productName, ENT_QUOTES ) . '"></div>'
        );
        $out->addHTML( '<div id="traceability-tree-details"></div>' );

        $this->displayProductInfo( $out, $productName );
    }

    private function displayProductInfo( $out, $productName ) {
        $title = Title::newFromText( $productName, NS_MAIN );
        if ( $title ) {
            $out->addHTML( '<p><a href="' . $title->getLocalURL() . '">View Product Page</a></p>' );
        }

        $transformations = Traceability::getProductTransformations( $productName );
        if ( !empty( $transformations ) ) {
            $out->addHTML( '<h3>Transformations</h3><ul>' );
            foreach ( $transformations as $transformation ) {
                $out->addHTML( '<li><a href="' . Title::newFromText( $transformation )->getLocalURL() . '">' . htmlspecialchars( $transformation ) . '</a></li>' );
            }
            $out->addHTML( '</ul>' );
        }
    }
}
