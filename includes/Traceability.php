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
        if ( $title && $title->getNamespace() === NS_MAIN ) {
            $productName = $title->getText();
            if ( self::isProductPage( $productName ) ) {
                $out->addModules( 'ext.wikitraceability' );
                $treeHtml = self::renderTraceabilityTree( $productName );
                $html .= $treeHtml;
            }
        }
    }

    private static function isProductPage( string $pageName ): bool {
        if ( class_exists( '\SMW\StoreFactory' ) ) {
            $store = \SMW\StoreFactory::getStore();
            $semanticData = $store->getSemanticData( \SMW\DIWikiPage::newFromTitle( \Title::newFromText( $pageName ) ) );
            return $semanticData->hasProperty( \SMW\DIProperty::newFromUserLabel( 'ProductName' ) );
        }
        return false;
    }

    public static function renderTraceabilityTree( string $productName ): string {
        return '<div class="wikitraceability-tree-container" data-product="' .
            htmlspecialchars( $productName, ENT_QUOTES ) . '"></div>';
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
            $subject = \SMW\DIWikiPage::newFromTitle( \Title::newFromText( $transformationName ) );
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
