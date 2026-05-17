<?php

namespace WikiTraceability;

use SpecialPage;

class SpecialTransformation extends SpecialPage {
    public function __construct() {
        parent::__construct( 'TraceabilityTransformation' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $out = $this->getOutput();

        $out->addWikiTextAsInterface( '== Transformation entry ==\nThis page will allow users to create and edit traceability transformations.' );
        $out->addHTML(
            '<form method="post" class="mw-htmlform">'
            . '<label for="wpSourceProduct">Source product</label><br />'
            . '<input id="wpSourceProduct" name="wpSourceProduct" type="text" class="mw-htmlform-field" /><br />'
            . '<label for="wpTargetProduct">Target product</label><br />'
            . '<input id="wpTargetProduct" name="wpTargetProduct" type="text" class="mw-htmlform-field" /><br />'
            . '<input type="submit" value="Create transformation" class="mw-htmlform-submit" />'
            . '</form>'
        );
    }
}
