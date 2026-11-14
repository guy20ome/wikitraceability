<?php

namespace WikiTraceability;

use OutputPage;
class Traceability {
    public static function init(): void {
        // Extension initialization and shared helper entrypoint.
        // Register hook for adding traceability tree to product pages
    }

    public static function onOutputPageBeforeHTML( OutputPage &$out, string &$html ): void {
        $title = $out->getTitle();
        if ( $title && $title->getNamespace() === NS_MAIN && $out->getUser()->isRegistered() ) {
            $productName = $title->getText();
            $entityType = self::getEntityType( $productName );
            if ( $entityType === 'product' ) {
                $out->addModules( 'ext.wikitraceability' );
                $detailsHtml = self::renderProductDetails( $productName );
                $managementLinks = self::renderManagementLinks( $entityType, $productName );
                $treeHtml = self::renderTraceabilityTree( $productName );
                $html = $treeHtml . $detailsHtml . $managementLinks . $html;
            } elseif ( $entityType === 'transformation' ) {
                $detailsHtml = self::renderTransformationDetails( $productName );
                $managementLinks = self::renderManagementLinks( $entityType, $productName );
                $html = $detailsHtml . $managementLinks . $html;
            }
        }
    }

    public static function onMessagesPreLoad( string $title, &$message, string $code ): void {
        if ( strtolower( $title ) === 'mainpage' ) {
            $message = 'Special:TraceabilityProduct';
        }
    }

    private static function getEntityType( string $pageName ): ?string {
        if ( class_exists( '\SMW\StoreFactory' ) ) {
            $title = \Title::newFromText( $pageName, NS_MAIN );
            if ( !$title ) {
                return null;
            }
            $store = \SMW\StoreFactory::getStore();
            $semanticData = $store->getSemanticData( \SMW\DIWikiPage::newFromTitle( $title ) );
            if ( $semanticData->hasProperty( \SMW\DIProperty::newFromUserLabel( 'ProductName' ) ) ) {
                return 'product';
            }
            if ( $semanticData->hasProperty( \SMW\DIProperty::newFromUserLabel( 'TransformationName' ) ) ) {
                return 'transformation';
            }
        }
        return null;
    }

    private static function renderManagementLinks( string $entityType, string $pageName ): string {
        $specialPage = $entityType === 'product'
            ? 'Special:TraceabilityProduct'
            : 'Special:TraceabilityTransformation';
        $queryParameter = $entityType === 'product' ? 'product' : 'transformation';
        $specialTitle = \Title::newFromText( $specialPage );
        if ( !$specialTitle ) {
            return '';
        }
        $url = $specialTitle->getLocalURL( [ $queryParameter => $pageName ] );
        return '<p class="wikitraceability-management-links"><a href="' .
            htmlspecialchars( $url, ENT_QUOTES ) . '">Edit or delete ' . $entityType . '</a></p>';
    }

    public static function getProductDetails( string $productName ): array {
        return self::getPageProperties( $productName, [
            'ProductName', 'ProductBrand', 'ProductBarcode', 'ProductWeight',
            'ProductWeightUnit', 'ProductDescription', 'ProductPrice', 'ProductCategory',
            'ProductSource', 'ProductDataLicense', 'ProductSourceURL'
        ] );
    }

    private static function getPageProperties( string $pageName, array $propertyLabels ): array {
        $details = [];
        $title = \Title::newFromText( $pageName, NS_MAIN );
        if ( !$title || !class_exists( '\SMW\StoreFactory' ) ) {
            return $details;
        }

        $store = \SMW\StoreFactory::getStore();
        $semanticData = $store->getSemanticData( \SMW\DIWikiPage::newFromTitle( $title ) );
        foreach ( $propertyLabels as $propertyLabel ) {
            $values = $semanticData->getPropertyValues(
                \SMW\DIProperty::newFromUserLabel( $propertyLabel )
            );
            if ( !$values ) {
                continue;
            }
            $value = $values[0];
            if ( $value instanceof \SMW\DIWikiPage ) {
                $details[$propertyLabel] = $value->getTitle()->getText();
            } elseif ( $value instanceof \SMWDataValue ) {
                $details[$propertyLabel] = $value->getWikiValue();
            }
        }
        return $details;
    }

    public static function hasProductTransformations( string $productName ): bool {
        if ( !class_exists( '\SMW\StoreFactory' ) ) {
            return true;
        }
        $title = \Title::newFromText( $productName, NS_MAIN );
        if ( !$title ) {
            return true;
        }
        $product = \SMW\DIWikiPage::newFromTitle( $title );
        $store = \SMW\StoreFactory::getStore();
        foreach ( [ 'HasInputProducts', 'HasOutputProducts' ] as $propertyLabel ) {
            foreach ( $store->getPropertySubjects(
                \SMW\DIProperty::newFromUserLabel( $propertyLabel ),
                $product
            ) as $subject ) {
                if ( $subject instanceof \SMW\DIWikiPage ) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function renderTraceabilityTree( string $productName ): string {
        return '<div class="wikitraceability-tree-container" data-product="' .
            htmlspecialchars( $productName, ENT_QUOTES ) . '"></div>';
    }

    public static function renderProductDetails( string $productName ): string {
        $details = self::getProductDetails( $productName );
        if ( empty( $details ) ) {
            return '';
        }
        $html = '<section class="wikitraceability-details wikitraceability-product-details">'
            . '<h2>' . htmlspecialchars( $details['ProductName'] ?? $productName ) . ' — Product Details</h2>'
            . '<table class="wikitable">';
        $fields = [
            'ProductName' => 'Product Name',
            'ProductBrand' => 'Brand',
            'ProductBarcode' => 'Barcode',
            'ProductWeight' => 'Weight',
            'ProductWeightUnit' => 'Weight Unit',
            'ProductDescription' => 'Description',
            'ProductPrice' => 'Price (USD)',
            'ProductCategory' => 'Category',
            'ProductSource' => 'Source',
            'ProductDataLicense' => 'Data License'
        ];
        foreach ( $fields as $property => $label ) {
            $value = $details[$property] ?? '';
            if ( $value === '' ) {
                continue;
            }
            $cell = htmlspecialchars( $value );
            if ( $property === 'ProductSource' && ( $details['ProductSourceURL'] ?? '' ) !== '' ) {
                $cell = '<a href="' . htmlspecialchars( $details['ProductSourceURL'], ENT_QUOTES ) .
                    '" target="_blank" rel="noopener noreferrer">' . $cell . '</a>';
            }
            $html .= '<tr>'
                . '<th>' . htmlspecialchars( $label ) . '</th>'
                . '<td>' . $cell . '</td>'
                . '</tr>';
        }
        $html .= '</table></section>';
        return $html;
    }

    public static function renderTransformationDetails( string $transformationName ): string {
        $details = self::getTransformationDetails( $transformationName );
        if ( empty( $details ) ) {
            return '';
        }
        $html = '<section class="wikitraceability-details wikitraceability-transformation-details">'
            . '<h2>' . htmlspecialchars( $details['TransformationName'][0] ?? $transformationName ) . ' — Transformation Details</h2>'
            . '<table class="wikitable">';
        $fields = [
            'TransformationName' => 'Name',
            'TransformationType' => 'Type',
            'InputQuantityPerUnit' => 'Input Quantity per Unit',
            'OutputQuantityPerUnit' => 'Output Quantity per Unit',
            'TransformationDescription' => 'Description'
        ];
        foreach ( $fields as $property => $label ) {
            $values = $details[$property] ?? [];
            if ( empty( $values ) ) {
                continue;
            }
            $html .= '<tr>'
                . '<th>' . htmlspecialchars( $label ) . '</th>'
                . '<td>' . htmlspecialchars( implode( ', ', $values ) ) . '</td>'
                . '</tr>';
        }
        $html .= '</table>';

        $html .= '<h3>Input Products</h3>';
        $inputValues = $details['HasInputProducts'] ?? [];
        if ( !empty( $inputValues ) ) {
            $html .= '<ul>';
            foreach ( $inputValues as $input ) {
                $html .= '<li>' . htmlspecialchars( $input ) . '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p class="wikitraceability-details-none">None</p>';
        }

        $html .= '<h3>Output Products</h3>';
        $outputValues = $details['HasOutputProducts'] ?? [];
        if ( !empty( $outputValues ) ) {
            $html .= '<ul>';
            foreach ( $outputValues as $output ) {
                $html .= '<li>' . htmlspecialchars( $output ) . '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p class="wikitraceability-details-none">None</p>';
        }

        $html .= '</section>';
        return $html;
    }

    public static function getProductTransformations( string $productName ): array {
        $transformations = [];
        if ( class_exists( '\SMW\StoreFactory' ) ) {
            $store = \SMW\StoreFactory::getStore();
            $title = \Title::newFromText( $productName );
            if ( !$title ) {
                return $transformations;
            }
            $product = \SMW\DIWikiPage::newFromTitle( $title );
            $subjects = $store->getPropertySubjects(
                \SMW\DIProperty::newFromUserLabel( 'HasOutputProducts' ),
                $product
            );
            foreach ( $subjects as $transformation ) {
                if ( $transformation instanceof \SMW\DIWikiPage ) {
                    $transformationData = $store->getSemanticData( $transformation );
                    $nameValues = $transformationData->getPropertyValues(
                        \SMW\DIProperty::newFromUserLabel( 'TransformationName' )
                    );
                    $transformations[] = $nameValues && $nameValues[0] instanceof \SMW\DIWikiPage
                        ? $nameValues[0]->getTitle()->getText()
                        : $transformation->getTitle()->getText();
                }
            }
        }
        return $transformations;
    }

    public static function getTransformationDetails( string $transformationName ): array {
        $details = [];
        if ( class_exists( '\SMW\StoreFactory' ) ) {
            $store = \SMW\StoreFactory::getStore();
            $title = \Title::newFromText( $transformationName, NS_MAIN );
            if ( !$title ) {
                return $details;
            }
            $subject = \SMW\DIWikiPage::newFromTitle( $title );
            $semanticData = $store->getSemanticData( $subject );

            $properties = [
                'TransformationName', 'TransformationType', 'InputQuantityPerUnit',
                'OutputQuantityPerUnit', 'TransformationDescription', 'HasInputProducts', 'HasOutputProducts'
            ];

            foreach ( $properties as $propertyLabel ) {
                $property = \SMW\DIProperty::newFromUserLabel( $propertyLabel );
                $values = $semanticData->getPropertyValues( $property );
                if ( !empty( $values ) ) {
                    $details[ $propertyLabel ] = [];
                    foreach ( $values as $value ) {
                        if ( $value instanceof \SMW\DIWikiPage ) {
                            $details[ $propertyLabel ][] = $value->getTitle()->getText();
                        } elseif ( $value instanceof \SMWDataValue ) {
                            $details[ $propertyLabel ][] = $value->getWikiValue();
                        }
                    }
                }
            }
        }
        return $details;
    }
}
