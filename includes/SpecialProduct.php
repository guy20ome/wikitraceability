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

        $out->addWikiTextAsInterface(
            'Search products saved in this wiki or find products in the Open Food Facts database.'
        );

        // Handle form submission
        $request = $this->getRequest();
        $action = $request->getText( 'wpAction' );
        $productName = trim( $request->getText( 'product' ) );
        if ( $action !== '' && $request->wasPosted() ) {
            if ( !$this->getUser()->isRegistered() ) {
                $out->addHTML( '<p class="error">You must be logged in to change products.</p>' );
            } elseif ( !$this->getContext()->getCsrfTokenSet()->matchToken( $request->getVal( 'wpEditToken' ) ) ) {
                $out->addHTML( '<p class="error">Your session token is invalid. Reload the page and try again.</p>' );
            } elseif ( $action === 'delete' ) {
                if ( $this->handleProductDeletion( $productName, $out ) ) {
                    $productName = '';
                }
            } elseif ( $action === 'create' || $action === 'update' ) {
                $this->handleProductSave( $request, $out, $action === 'update', $productName );
            }
        }

        if ( $productName !== '' && !$this->getUser()->isRegistered() ) {
            $out->addHTML( '<p class="error">Log in to edit or delete this product.</p>' );
            $productName = '';
        }
        $isEditing = $productName !== '' && Traceability::getProductDetails( $productName );
        if ( $productName !== '' && !$isEditing && $this->getUser()->isRegistered() ) {
            $out->addHTML( '<p class="error">This product is not registered yet. You can add its details below.</p>' );
        }
        $this->displayProductForm(
            $out,
            $isEditing ? $productName : '',
            !$isEditing && $this->getUser()->isRegistered() ? $productName : ''
        );
    }

    private function displayProductForm( $out, string $editingProduct = '', string $prefilledProduct = '' ) {
        $details = $editingProduct !== '' ? Traceability::getProductDetails( $editingProduct ) : [];
        $fields = [
            'wpProductName' => 'ProductName',
            'wpProductBrand' => 'ProductBrand',
            'wpProductBarcode' => 'ProductBarcode',
            'wpProductWeight' => 'ProductWeight',
            'wpProductWeightUnit' => 'ProductWeightUnit',
            'wpProductDescription' => 'ProductDescription',
            'wpProductPrice' => 'ProductPrice',
            'wpProductCategory' => 'ProductCategory',
            'wpProductSource' => 'ProductSource'
        ];
        $values = [];
        foreach ( $fields as $field => $property ) {
            $values[$field] = htmlspecialchars( $details[$property] ?? '', ENT_QUOTES );
        }
        if ( $editingProduct === '' && $prefilledProduct !== '' ) {
            $values['wpProductName'] = htmlspecialchars( $prefilledProduct, ENT_QUOTES );
        }
        $form = '';
        if ( $editingProduct === '' ) {
            $form .= '<section class="wikitraceability-local-lookup" aria-labelledby="local-lookup-heading">'
            . '<h2 id="local-lookup-heading">Search products in this wiki</h2>'
            . '<p>Find products already saved in this traceability catalog.</p>'
            . '<form id="wikitraceability-local-search">'
            . '<label for="wikitraceability-local-search-term">Product name</label><br />'
            . '<input id="wikitraceability-local-search-term" type="search" class="mw-htmlform-field" minlength="2" maxlength="100" required /> '
            . '<button type="submit" class="mw-htmlform-submit">Search this wiki</button>'
            . '</form>'
            . '<div id="wikitraceability-local-results" aria-live="polite"></div>'
            . '</section>'
            . '<section class="wikitraceability-off-lookup" aria-labelledby="off-lookup-heading">'
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
            . '</section>';
        }
        $form .= '<form method="post" class="mw-htmlform">'
            . '<input type="hidden" name="wpAction" value="' . ( $editingProduct !== '' ? 'update' : 'create' ) . '" />'
            . ( $editingProduct !== '' ? '<input type="hidden" name="product" value="' . htmlspecialchars( $editingProduct, ENT_QUOTES ) . '" />' : '' )
            . '<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getContext()->getCsrfTokenSet()->getToken()->toString(), ENT_QUOTES ) . '" />'
            . '<input id="wpProductSource" name="wpProductSource" type="hidden" value="' . $values['wpProductSource'] . '" />'
            . '<label for="wpProductName">Product Name</label><br />'
            . '<input id="wpProductName" name="wpProductName" type="text" class="mw-htmlform-field" value="' . $values['wpProductName'] . '" required'
            . ( $editingProduct !== '' ? ' readonly' : '' ) . ' /><br />'
            . ( $editingProduct !== '' ? '<p>The product name is fixed because it identifies this product in transformations.</p>' : '' )
            . '<label for="wpProductBrand">Brand</label><br />'
            . '<input id="wpProductBrand" name="wpProductBrand" type="text" class="mw-htmlform-field" value="' . $values['wpProductBrand'] . '" /><br />'
            . '<label for="wpProductBarcode">Barcode</label><br />'
            . '<input id="wpProductBarcode" name="wpProductBarcode" type="text" class="mw-htmlform-field" value="' . $values['wpProductBarcode'] . '" /><br />'
            . '<label for="wpProductWeight">Weight</label><br />'
            . '<input id="wpProductWeight" name="wpProductWeight" type="number" step="any" class="mw-htmlform-field" value="' . $values['wpProductWeight'] . '" /><br />'
            . '<label for="wpProductWeightUnit">Weight Unit</label><br />'
            . '<select id="wpProductWeightUnit" name="wpProductWeightUnit" class="mw-htmlform-field">'
            . $this->renderWeightUnitOptions( $values['wpProductWeightUnit'] )
            . '</select><br />'
            . '<label for="wpProductDescription">Description</label><br />'
            . '<textarea id="wpProductDescription" name="wpProductDescription" class="mw-htmlform-field">' . $values['wpProductDescription'] . '</textarea><br />'
            . '<label for="wpProductPrice">Price (USD)</label><br />'
            . '<input id="wpProductPrice" name="wpProductPrice" type="number" step="0.01" class="mw-htmlform-field" value="' . $values['wpProductPrice'] . '" /><br />'
            . '<label for="wpProductCategory">Category</label><br />'
            . '<input id="wpProductCategory" name="wpProductCategory" type="text" class="mw-htmlform-field" value="' . $values['wpProductCategory'] . '" /><br />'
            . '<input type="submit" value="' . ( $editingProduct !== '' ? 'Update Product' : 'Create Product' ) . '" class="mw-htmlform-submit" />'
            . '</form>';
        if ( $editingProduct !== '' && $this->getAuthority()->isAllowed( 'edit' ) &&
            $this->getAuthority()->isAllowed( 'wikitraceability-delete' ) ) {
            if ( Traceability::hasProductTransformations( $editingProduct ) ) {
                $form .= '<p class="error">This product is used by a transformation and cannot be deleted.</p>';
            } else {
                $form .= '<form method="post" class="mw-htmlform" onsubmit="return confirm(\'Delete this product permanently?\')">'
                    . '<input type="hidden" name="wpAction" value="delete" />'
                    . '<input type="hidden" name="product" value="' . htmlspecialchars( $editingProduct, ENT_QUOTES ) . '" />'
                    . '<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getContext()->getCsrfTokenSet()->getToken()->toString(), ENT_QUOTES ) . '" />'
                    . '<button type="submit" class="mw-htmlform-submit">Delete Product</button></form>';
            }
        }

        $out->addHTML( $form );
    }

    private function renderWeightUnitOptions( string $selected ): string {
        $options = '';
        foreach ( [ 'kg', 'g', 'lbs', 'oz' ] as $unit ) {
            $options .= '<option value="' . $unit . '"' . ( $selected === $unit ? ' selected' : '' ) . '>' . $unit . '</option>';
        }
        return $options;
    }

    private function handleProductSave( $request, $out, bool $isUpdate, string $editingProduct ) {
        $productName = trim( $request->getText( 'wpProductName' ) );

        if ( !$productName ) {
            $out->addHTML( '<p class="error">Product name is required.</p>' );
            return;
        }

        // Find existing product or create new page
        $title = Title::newFromText( $productName, NS_MAIN );
        $editingTitle = $isUpdate ? Title::newFromText( $editingProduct, NS_MAIN ) : null;
        if ( !$title || ( $isUpdate && ( !$editingTitle || $title->getText() !== $editingTitle->getText() ) ) ) {
            $out->addHTML( '<p class="error">Invalid product name.</p>' );
            return;
        }
        if ( $isUpdate ) {
            if ( !Traceability::getProductDetails( $editingTitle->getText() ) ) {
                $out->addHTML( '<p class="error">Product not found.</p>' );
                return;
            }
        } elseif ( $title->exists() ) {
            $out->addHTML( '<p class="error">A page with this name already exists. Choose a different product name.</p>' );
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
                $semanticContent .= '[[' . $property . '::' . self::escapeSemanticValue( $value ) . '| ]]' . "\n";
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
            ( $isUpdate ? 'Updated' : 'Created' ) . ' via TraceabilityProduct special page'
        );

        if ( $status->isGood() ) {
            $out->addHTML( '<p class="success">Product <strong>' . htmlspecialchars( $productName ) . '</strong> ' .
                ( $isUpdate ? 'updated' : 'created' ) . ' with SMW properties.</p>' );
            $out->addHTML( '<p><a href="' . $title->getLocalURL() . '">View product page</a></p>' );
        } else {
            $out->addHTML( '<p class="error">Failed to save product: ' .
                htmlspecialchars( $status->getMessage()->text() ) . '</p>' );
        }
    }

    private function handleProductDeletion( string $productName, $out ): bool {
        if ( !$this->getAuthority()->isAllowed( 'edit' ) ||
            !$this->getAuthority()->isAllowed( 'wikitraceability-delete' ) ) {
            $out->addHTML( '<p class="error">You do not have permission to delete products.</p>' );
            return false;
        }
        if ( !Traceability::getProductDetails( $productName ) ) {
            $out->addHTML( '<p class="error">Product not found.</p>' );
            return false;
        }
        if ( Traceability::hasProductTransformations( $productName ) ) {
            $out->addHTML( '<p class="error">This product is used by a transformation and cannot be deleted.</p>' );
            return false;
        }

        $title = Title::newFromText( $productName, NS_MAIN );
        $wikiPage = new WikiPage( $title );
        $error = '';
        $status = $wikiPage->doDeleteArticleReal(
            'Deleted via TraceabilityProduct special page',
            $this->getUser(),
            false,
            null,
            $error
        );
        if ( $status->isGood() ) {
            $out->addHTML( '<p class="success">Product <strong>' . htmlspecialchars( $productName ) . '</strong> deleted.</p>' );
            return true;
        } else {
            $out->addHTML( '<p class="error">Failed to delete product: ' . htmlspecialchars( $status->getMessage()->text() ) . '</p>' );
            return false;
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
