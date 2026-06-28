<?php

namespace WikiTraceability;

use SpecialPage;

class SpecialProduct extends SpecialPage {
    public function __construct() {
        parent::__construct( 'TraceabilityProduct' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $out = $this->getOutput();

        $out->addWikiTextAsInterface( '== Product entry ==\nThis page will allow users to create and edit traceability products.' );
        $out->addHTML(
            '<form method="post" class="mw-htmlform">'
            . '<label for="wpProductName">Product name</label><br />'
            . '<input id="wpProductName" name="wpProductName" type="text" class="mw-htmlform-field" /><br />'
            . '<input type="submit" value="Create product" class="mw-htmlform-submit" />'
            . '</form>'
        );
    }
}
