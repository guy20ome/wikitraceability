<?php

namespace WikiTraceability;

use SpecialPage;
use WikiPage;
use WikitextContent;
use Title;

class SpecialTransformation extends SpecialPage {
    public function __construct() {
        parent::__construct( 'TraceabilityTransformation' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $out = $this->getOutput();

        $out->addWikiTextAsInterface( '== Transformation entry ==\nCreate or edit traceability transformations with Semantic MediaWiki integration.' );

        $request = $this->getRequest();
        $action = $request->getText( 'wpAction' );

        if ( $action === 'create' && $this->getUser()->isRegistered() ) {
            $this->handleTransformationCreation( $request, $out );
        }

        $this->displayTransformationForm( $out );
    }

    private function displayTransformationForm( $out ) {
        $form = '<form method="post" class="mw-htmlform">'
            . '<input type="hidden" name="wpAction" value="create" />'
            . '<label for="wpTransformationName">Transformation Name</label><br />'
            . '<input id="wpTransformationName" name="wpTransformationName" type="text" class="mw-htmlform-field" required /><br />'
            . '<label for="wpTransformationType">Transformation Type</label><br />'
            . '<select id="wpTransformationType" name="wpTransformationType" class="mw-htmlform-field">'
            . '<option value="processing">Processing</option>'
            . '<option value="mixing">Mixing</option>'
            . '<option value="assembly">Assembly</option>'
            . '<option value="refining">Refining</option>'
            . '<option value="packaging">Packaging</option>'
            . '</select><br />'
            . '<label for="wpInputProducts">Input Products (comma-separated page names)</label><br />'
            . '<input id="wpInputProducts" name="wpInputProducts" type="text" class="mw-htmlform-field" required /><br />'
            . '<label for="wpOutputProducts">Output Products (comma-separated page names)</label><br />'
            . '<input id="wpOutputProducts" name="wpOutputProducts" type="text" class="mw-htmlform-field" required /><br />'
            . '<label for="wpInputQuantityPerUnit">Input Quantity per Unit</label><br />'
            . '<input id="wpInputQuantityPerUnit" name="wpInputQuantityPerUnit" type="number" step="any" class="mw-htmlform-field" /><br />'
            . '<label for="wpOutputQuantityPerUnit">Output Quantity per Unit</label><br />'
            . '<input id="wpOutputQuantityPerUnit" name="wpOutputQuantityPerUnit" type="number" step="any" class="mw-htmlform-field" /><br />'
            . '<label for="wpTransformationDescription">Description</label><br />'
            . '<textarea id="wpTransformationDescription" name="wpTransformationDescription" class="mw-htmlform-field"></textarea><br />'
            . '<input type="submit" value="Create Transformation" class="mw-htmlform-submit" />'
            . '</form>';

        $out->addHTML( $form );
    }

    private function handleTransformationCreation( $request, $out ) {
        $transformationName = trim( $request->getText( 'wpTransformationName' ) );

        if ( !$transformationName ) {
            $out->addHTML( '<p class="error">Transformation name is required.</p>' );
            return;
        }

        $title = Title::newFromText( 'Transformation/' . $transformationName, NS_MAIN );
        if ( !$title ) {
            $out->addHTML( '<p class="error">Invalid transformation name.</p>' );
            return;
        }

        $transformationType = $request->getText( 'wpTransformationType' );
        $inputProducts = trim( $request->getText( 'wpInputProducts' ) );
        $outputProducts = trim( $request->getText( 'wpOutputProducts' ) );
        $inputQuantity = $request->getText( 'wpInputQuantityPerUnit' );
        $outputQuantity = $request->getText( 'wpOutputQuantityPerUnit' );
        $description = $request->getText( 'wpTransformationDescription' );

        if ( !$inputProducts || !$outputProducts ) {
            $out->addHTML( '<p class="error">Input and output products are required.</p>' );
            return;
        }

        $inputProductsArray = array_map( 'trim', explode( ',', $inputProducts ) );
        $outputProductsArray = array_map( 'trim', explode( ',', $outputProducts ) );

        $properties = [
            'TransformationName' => $transformationName,
            'TransformationType' => $transformationType,
            'InputQuantityPerUnit' => $inputQuantity,
            'OutputQuantityPerUnit' => $outputQuantity,
            'TransformationDescription' => $description
        ];
        $semanticContent = '';
        foreach ( $properties as $property => $value ) {
            if ( $value !== '' ) {
                $semanticContent .= '[[' . $property . '::' . self::escapeSemanticValue( $value ) . "]]\n";
            }
        }

        foreach ( $inputProductsArray as $product ) {
            if ( $product ) {
                $semanticContent .= '[[HasInputProducts::' . self::escapeSemanticValue( $product ) . "]]\n";
            }
        }

        foreach ( $outputProductsArray as $product ) {
            if ( $product ) {
                $semanticContent .= '[[HasOutputProducts::' . self::escapeSemanticValue( $product ) . "]]\n";
            }
        }

        $wikiPage = new WikiPage( $title );
        $content = new WikitextContent( $semanticContent );
        $status = $wikiPage->doUserEditContent(
            $content,
            $this->getAuthority(),
            'Created via TraceabilityTransformation special page'
        );

        if ( $status->isGood() ) {
            $out->addHTML( '<p class="success">Transformation <strong>' . htmlspecialchars( $transformationName ) . '</strong> created with SMW properties.</p>' );
            $out->addHTML( '<p><a href="' . $title->getLocalURL() . '">View transformation page</a></p>' );
        } else {
            $out->addHTML( '<p class="error">Failed to create transformation: ' . $status->getMessage()->text() . '</p>' );
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
