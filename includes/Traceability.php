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
                $treeHtml = self::renderTraceabilityTree( $productName, true );
                $html .= $treeHtml . self::renderManagementLinks( $entityType, $productName );
            } elseif ( $entityType === 'transformation' ) {
                $html .= self::renderManagementLinks( $entityType, $productName );
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
            'ProductSource'
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

    public static function renderTraceabilityTree( string $productName, bool $canEdit = false ): string {
        return '<div class="wikitraceability-tree-container" data-product="' .
            htmlspecialchars( $productName, ENT_QUOTES ) . '" data-can-edit="' .
            ( $canEdit ? 'true' : 'false' ) . '"></div>';
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
