<?php

namespace WikiTraceability;

use ApiBase;
use MediaWiki\MediaWikiServices;

class ApiOpenFoodFacts extends ApiBase {
    public function execute() {
        $params = $this->extractRequestParams();
        $search = trim( $params['search'] );
        if ( strlen( $search ) < 2 || strlen( $search ) > 400 ) {
            $this->dieWithError( 'wikitraceability-openfoodfacts-invalid-search', 'invalid-search' );
        }

        $query = preg_match( '/^\d{8,14}$/D', $search )
            ? 'code:"' . $search . '"'
            : $search;
        $url = 'https://search.openfoodfacts.org/search?' . http_build_query( [
            'q' => $query,
            'page_size' => 10,
            'fields' => 'code,product_name,brands,quantity,categories,ingredients_text'
        ] );

        $request = MediaWikiServices::getInstance()->getHttpRequestFactory()->create(
            $url,
            [ 'method' => 'GET', 'timeout' => 10 ],
            __METHOD__
        );
        $request->setHeader(
            'User-Agent',
            'WikiTraceability/0.3.0 (https://github.com/guy20ome/wikitraceability)'
        );
        $status = $request->execute();
        if ( !$status->isOK() || $request->getStatus() < 200 || $request->getStatus() >= 300 ) {
            $failure = 'HTTP status ' . $request->getStatus();
            if ( !$status->isOK() ) {
                $failure .= ': ' . $status->getMessage()->text();
            }
            wfDebugLog(
                'wikitraceability',
                'Open Food Facts request failed: ' . $failure
            );
            error_log( 'WikiTraceability Open Food Facts request failed: ' . $failure );
            $this->dieWithError( 'wikitraceability-openfoodfacts-unavailable', 'openfoodfacts-unavailable' );
        }

        $response = json_decode( $request->getContent(), true );
        if ( !is_array( $response ) || !isset( $response['hits'] ) || !is_array( $response['hits'] ) ) {
            $this->dieWithError( 'wikitraceability-openfoodfacts-invalid-response', 'openfoodfacts-invalid-response' );
        }

        $products = [];
        foreach ( $response['hits'] as $product ) {
            if ( !is_array( $product ) || empty( $product['product_name'] ) ) {
                continue;
            }
            $products[] = [
                'code' => $this->getStringValue( $product, 'code' ),
                'product_name' => $this->getStringValue( $product, 'product_name' ),
                'brands' => $this->getStringValue( $product, 'brands' ),
                'quantity' => $this->getStringValue( $product, 'quantity' ),
                'categories' => $this->getStringValue( $product, 'categories' ),
                'ingredients_text' => $this->getStringValue( $product, 'ingredients_text' )
            ];
        }

        $this->getResult()->addValue( null, $this->getModuleName(), [ 'products' => $products ] );
    }

    private function getStringValue( array $product, string $key ): string {
        if ( !isset( $product[$key] ) ) {
            return '';
        }
        if ( is_scalar( $product[$key] ) ) {
            return trim( (string)$product[$key] );
        }
        if ( is_array( $product[$key] ) ) {
            $values = [];
            foreach ( $product[$key] as $value ) {
                if ( is_scalar( $value ) && trim( (string)$value ) !== '' ) {
                    $values[] = trim( (string)$value );
                }
            }
            return implode( ', ', $values );
        }
        return '';
    }

    public function getAllowedParams() {
        return [
            'search' => [
                self::PARAM_REQUIRED => true,
                self::PARAM_TYPE => 'string'
            ]
        ];
    }

}
