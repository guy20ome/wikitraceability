<?php

namespace WikiTraceability;

use OutputPage;
use Skin;

class Traceability {
    public static function init(): void {
        // Extension initialization and shared helper entrypoint.
        // Register hook for adding traceability tree to product pages
    }

    public static function onOutputPageBeforeHTML( OutputPage &$out, string &$html ): void {
        $title = $out->getTitle();
        if ( $title && $title->getNamespace() === NS_MAIN ) {
            $productName = $title->getText();
            if ( self::isProductPage( $productName ) ) {
                $treeHtml = self::renderTraceabilityTree( $productName );
                $html = str_replace( '</body>', $treeHtml . '</body>', $html );
            }
        }
    }

    private static function isProductPage( string $pageName ): bool {
        if ( class_exists( '\SMW\Store' ) ) {
            $store = \SMW\Store::getInstance();
            $semanticData = $store->getSemanticData( \SMWDIWikiPage::newFromTitle( \Title::newFromText( $pageName ) ) );
            return $semanticData->hasProperty( \SMWDIProperty::newFromUserLabel( 'ProductName' ) );
        }
        return false;
    }

    public static function renderTraceabilityTree( string $productName ): string {
        $html = '<div class="wikitraceability-tree-container" data-product="' . htmlspecialchars( $productName ) . '"></div>';
        $html .= '<script>if (typeof mw !== "undefined" && mw.WikiTraceability) { mw.WikiTraceability.initTree("' . htmlspecialchars( $productName ) . '"); }</script>';
        return $html;
    }

    public static function getProductTransformations( string $productName ): array {
        $transformations = [];
        if ( class_exists( '\SMW\Store' ) ) {
            $store = \SMW\Store::getInstance();
            $subject = \SMWDIWikiPage::newFromTitle( \Title::newFromText( $productName ) );
            $semanticData = $store->getSemanticData( $subject );
            
            $hasTransformationProperty = \SMWDIProperty::newFromUserLabel( 'HasTransformation' );
            $hasOutputProductsProperty = \SMWDIProperty::newFromUserLabel( 'HasOutputProducts' );
            $hasInputProductsProperty = \SMWDIProperty::newFromUserLabel( 'HasInputProducts' );

            $transformationsProperty = $semanticData->getPropertyValues( $hasTransformationProperty );
            foreach ( $transformationsProperty as $transformation ) {
                if ( $transformation instanceof \SMWDIWikiPage ) {
                    $transformations[] = $transformation->getTitle()->getText();
                }
            }
        }
        return $transformations;
    }

    public static function getTransformationDetails( string $transformationName ): array {
        $details = [];
        if ( class_exists( '\SMW\Store' ) ) {
            $store = \SMW\Store::getInstance();
            $subject = \SMWDIWikiPage::newFromTitle( \Title::newFromText( $transformationName ) );
            $semanticData = $store->getSemanticData( $subject );

            $properties = [
                'TransformationName', 'TransformationType', 'InputQuantityPerUnit',
                'OutputQuantityPerUnit', 'TransformationDescription', 'HasInputProducts', 'HasOutputProducts'
            ];

            foreach ( $properties as $propertyLabel ) {
                $property = \SMWDIProperty::newFromUserLabel( $propertyLabel );
                $values = $semanticData->getPropertyValues( $property );
                if ( !empty( $values ) ) {
                    $details[ $propertyLabel ] = [];
                    foreach ( $values as $value ) {
                        if ( $value instanceof \SMWDIWikiPage ) {
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
