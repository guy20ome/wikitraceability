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
        $out->addModules( 'ext.wikitraceability' );

        $out->addWikiTextAsInterface( '== Transformation entry ==\nCreate or edit traceability transformations with Semantic MediaWiki integration.' );

        $request = $this->getRequest();
        $action = $request->getText( 'wpAction' );
        $transformationPage = trim( $request->getText( 'transformation' ) );
        if ( $action !== '' && $request->wasPosted() ) {
            if ( !$this->getUser()->isRegistered() ) {
                $out->addHTML( '<p class="error">You must be logged in to change transformations.</p>' );
            } elseif ( !$this->getContext()->getCsrfTokenSet()->matchToken( $request->getVal( 'wpEditToken' ) ) ) {
                $out->addHTML( '<p class="error">Your session token is invalid. Reload the page and try again.</p>' );
            } elseif ( $action === 'delete' ) {
                if ( $this->handleTransformationDeletion( $transformationPage, $out ) ) {
                    $transformationPage = '';
                }
            } elseif ( $action === 'create' || $action === 'update' ) {
                $this->handleTransformationSave( $request, $out, $action === 'update', $transformationPage );
            }
        }

        if ( $transformationPage !== '' && !$this->getUser()->isRegistered() ) {
            $out->addHTML( '<p class="error">Log in to edit or delete this transformation.</p>' );
            $transformationPage = '';
        }
        $isEditing = $transformationPage !== '' && Traceability::getTransformationDetails( $transformationPage );
        if ( $transformationPage !== '' && !$isEditing ) {
            $out->addHTML( '<p class="error">Transformation not found.</p>' );
        }
        $this->displayTransformationForm( $out, $isEditing ? $transformationPage : '' );
    }

    private function displayTransformationForm( $out, string $editingPage = '' ) {
        $request = $this->getRequest();
        $details = $editingPage !== '' ? Traceability::getTransformationDetails( $editingPage ) : [];
        $inputProducts = $details['HasInputProducts'] ?? $request->getArray( 'wpInputProducts' );
        $outputProducts = $details['HasOutputProducts'] ?? $request->getArray( 'wpOutputProducts' );
        if ( !$inputProducts ) {
            $inputProducts = [ '' ];
        }
        if ( !$outputProducts && $editingPage === '' ) {
            $outputProducts = [ $request->getText( 'wpOutputProducts' ) ];
        }
        if ( !$outputProducts ) {
            $outputProducts = [ '' ];
        }
        $name = htmlspecialchars( $details['TransformationName'][0] ?? '', ENT_QUOTES );
        $type = $details['TransformationType'][0] ?? '';
        $inputQuantity = htmlspecialchars( $details['InputQuantityPerUnit'][0] ?? '', ENT_QUOTES );
        $outputQuantity = htmlspecialchars( $details['OutputQuantityPerUnit'][0] ?? '', ENT_QUOTES );
        $description = htmlspecialchars( $details['TransformationDescription'][0] ?? '', ENT_QUOTES );
        $form = '<form method="post" class="mw-htmlform wikitraceability-transformation-form">'
            . '<input type="hidden" name="wpAction" value="' . ( $editingPage !== '' ? 'update' : 'create' ) . '" />'
            . ( $editingPage !== '' ? '<input type="hidden" name="transformation" value="' . htmlspecialchars( $editingPage, ENT_QUOTES ) . '" />' : '' )
            . '<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getContext()->getCsrfTokenSet()->getToken()->toString(), ENT_QUOTES ) . '" />'
            . '<label for="wpTransformationName">Transformation Name</label><br />'
            . '<input id="wpTransformationName" name="wpTransformationName" type="text" class="mw-htmlform-field" value="' . $name . '" required'
            . ( $editingPage !== '' ? ' readonly' : '' ) . ' /><br />'
            . ( $editingPage !== '' ? '<p>The transformation name is fixed because it identifies this transformation page.</p>' : '' )
            . '<label for="wpTransformationType">Transformation Type</label><br />'
            . '<select id="wpTransformationType" name="wpTransformationType" class="mw-htmlform-field">'
            . $this->renderTransformationTypeOptions( $type )
            . '</select><br />'
            . $this->buildProductFields( 'input', $inputProducts )
            . $this->buildProductFields( 'output', $outputProducts )
            . '<p id="wikitraceability-product-suggestions-status" aria-live="polite"></p>'
            . '<label for="wpInputQuantityPerUnit">Input Quantity per Unit</label><br />'
            . '<input id="wpInputQuantityPerUnit" name="wpInputQuantityPerUnit" type="number" step="any" class="mw-htmlform-field" value="' . $inputQuantity . '" /><br />'
            . '<label for="wpOutputQuantityPerUnit">Output Quantity per Unit</label><br />'
            . '<input id="wpOutputQuantityPerUnit" name="wpOutputQuantityPerUnit" type="number" step="any" class="mw-htmlform-field" value="' . $outputQuantity . '" /><br />'
            . '<label for="wpTransformationDescription">Description</label><br />'
            . '<textarea id="wpTransformationDescription" name="wpTransformationDescription" class="mw-htmlform-field">' . $description . '</textarea><br />'
            . '<input type="submit" value="' . ( $editingPage !== '' ? 'Update Transformation' : 'Create Transformation' ) . '" class="mw-htmlform-submit" />'
            . '</form>';
        if ( $editingPage !== '' && $this->getAuthority()->isAllowed( 'edit' ) &&
            $this->getAuthority()->isAllowed( 'wikitraceability-delete' ) ) {
            $form .= '<form method="post" class="mw-htmlform" onsubmit="return confirm(\'Delete this transformation permanently?\')">'
                . '<input type="hidden" name="wpAction" value="delete" />'
                . '<input type="hidden" name="transformation" value="' . htmlspecialchars( $editingPage, ENT_QUOTES ) . '" />'
                . '<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getContext()->getCsrfTokenSet()->getToken()->toString(), ENT_QUOTES ) . '" />'
                . '<button type="submit" class="mw-htmlform-submit">Delete Transformation</button></form>';
        }

        $out->addHTML( $form );
    }

    private function renderTransformationTypeOptions( string $selected ): string {
        $types = [
            'processing' => 'Processing',
            'mixing' => 'Mixing',
            'assembly' => 'Assembly',
            'refining' => 'Refining',
            'packaging' => 'Packaging'
        ];
        $options = '';
        foreach ( $types as $value => $label ) {
            $options .= '<option value="' . $value . '"' . ( $selected === $value ? ' selected' : '' ) .
                '>' . $label . '</option>';
        }
        return $options;
    }

    private function buildProductFields( string $direction, array $products ): string {
        $label = ucfirst( $direction );
        $html = '<section class="wikitraceability-product-list" data-product-list="' . $direction . '">'
            . '<h3>' . $label . ' Products</h3>'
            . '<div class="wikitraceability-product-rows">';
        foreach ( array_values( $products ) as $index => $product ) {
            $id = 'wp' . ucfirst( $direction ) . 'Products-' . $index;
            $value = htmlspecialchars( trim( (string)$product ), ENT_QUOTES );
            $html .= '<div class="wikitraceability-product-row">'
                . '<label for="' . $id . '">' . $label . ' product ' . ( $index + 1 ) . '</label><br />'
                . '<input id="' . $id . '" name="wp' . ucfirst( $direction ) . 'Products[]" type="search" '
                    . 'class="mw-htmlform-field wikitraceability-product-selector" autocomplete="off" '
                    . 'aria-autocomplete="list" aria-expanded="false" aria-controls="' . $id . '-suggestions" '
                    . 'value="' . $value . '" required />'
                    . '<div id="' . $id . '-suggestions" class="wikitraceability-product-suggestions" role="listbox" hidden></div>'
                    . '<button type="button" class="wikitraceability-remove-product" data-remove-product '
                    . 'aria-label="Remove ' . strtolower( $label ) . ' product ' . ( $index + 1 ) . '"'
                    . ( count( $products ) === 1 ? ' disabled' : '' ) . '>Remove</button>'
                    . '</div>';
        }
        $html .= '</div><button type="button" class="mw-htmlform-submit" data-add-product="' . $direction . '">'
            . 'Add ' . strtolower( $label ) . ' product</button></section>';
        return $html;
    }

    private function handleTransformationSave( $request, $out, bool $isUpdate, string $editingPage ) {
        $transformationName = trim( $request->getText( 'wpTransformationName' ) );

        if ( !$transformationName ) {
            $out->addHTML( '<p class="error">Transformation name is required.</p>' );
            return;
        }

        $title = Title::newFromText( $isUpdate ? $editingPage : 'Transformation/' . $transformationName, NS_MAIN );
        if ( !$title ) {
            $out->addHTML( '<p class="error">Invalid transformation name.</p>' );
            return;
        }
        if ( $isUpdate ) {
            $details = Traceability::getTransformationDetails( $title->getText() );
            if ( !$details || ( $details['TransformationName'][0] ?? '' ) !== $transformationName ) {
                $out->addHTML( '<p class="error">Transformation not found.</p>' );
                return;
            }
        } elseif ( $title->exists() ) {
            $out->addHTML( '<p class="error">A page with this name already exists. Choose a different transformation name.</p>' );
            return;
        }

        $transformationType = $request->getText( 'wpTransformationType' );
        $inputProducts = $this->getProductList( $request->getArray( 'wpInputProducts' ) );
        $outputProducts = $this->getProductList( $request->getArray( 'wpOutputProducts' ) );
        $inputQuantity = $request->getText( 'wpInputQuantityPerUnit' );
        $outputQuantity = $request->getText( 'wpOutputQuantityPerUnit' );
        $description = $request->getText( 'wpTransformationDescription' );

        if ( !$inputProducts || !$outputProducts ) {
            $out->addHTML( '<p class="error">Input and output products are required.</p>' );
            return;
        }

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

        foreach ( $inputProducts as $product ) {
            $semanticContent .= '[[HasInputProducts::' . self::escapeSemanticValue( $product ) . "]]\n";
        }

        foreach ( $outputProducts as $product ) {
            $semanticContent .= '[[HasOutputProducts::' . self::escapeSemanticValue( $product ) . "]]\n";
        }

        $wikiPage = new WikiPage( $title );
        $content = new WikitextContent( $semanticContent );
        $status = $wikiPage->doUserEditContent(
            $content,
            $this->getAuthority(),
            ( $isUpdate ? 'Updated' : 'Created' ) . ' via TraceabilityTransformation special page'
        );

        if ( $status->isGood() ) {
            $out->addHTML( '<p class="success">Transformation <strong>' . htmlspecialchars( $transformationName ) . '</strong> ' .
                ( $isUpdate ? 'updated' : 'created' ) . ' with SMW properties.</p>' );
            $out->addHTML( '<p><a href="' . $title->getLocalURL() . '">View transformation page</a></p>' );
        } else {
            $out->addHTML( '<p class="error">Failed to save transformation: ' .
                htmlspecialchars( $status->getMessage()->text() ) . '</p>' );
        }
    }

    private function handleTransformationDeletion( string $transformationPage, $out ): bool {
        if ( !$this->getAuthority()->isAllowed( 'edit' ) ||
            !$this->getAuthority()->isAllowed( 'wikitraceability-delete' ) ) {
            $out->addHTML( '<p class="error">You do not have permission to delete transformations.</p>' );
            return false;
        }
        if ( !Traceability::getTransformationDetails( $transformationPage ) ) {
            $out->addHTML( '<p class="error">Transformation not found.</p>' );
            return false;
        }
        $title = Title::newFromText( $transformationPage, NS_MAIN );
        $wikiPage = new WikiPage( $title );
        $error = '';
        $status = $wikiPage->doDeleteArticleReal(
            'Deleted via TraceabilityTransformation special page',
            $this->getUser(),
            false,
            null,
            $error
        );
        if ( $status->isGood() ) {
            $out->addHTML( '<p class="success">Transformation deleted.</p>' );
            return true;
        } else {
            $out->addHTML( '<p class="error">Failed to delete transformation: ' .
                htmlspecialchars( $status->getMessage()->text() ) . '</p>' );
            return false;
        }
    }

    private function getProductList( ?array $products ): array {
        if ( $products === null ) {
            return [];
        }
        return array_values( array_filter( array_map(
            static function ( $product ) {
                return is_string( $product ) ? trim( $product ) : '';
            },
            $products
        ), static function ( $product ) {
            return $product !== '';
        } ) );
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
