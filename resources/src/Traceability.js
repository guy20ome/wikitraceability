( function ( $, mw ) {
    'use strict';

    if ( typeof mw === 'undefined' || typeof $ === 'undefined' ) {
        return;
    }

    console.log( 'WikiTraceability extension loaded.' );

    var TraceabilityTree = function ( container, productName ) {
        this.container = typeof container === 'string' ?
            document.getElementById( container ) : container;
        this.productName = productName;
        this.init();
    };

    TraceabilityTree.prototype = {
        init: function () {
            if ( !this.container || !this.productName ) {
                return;
            }
            this.loadTreeData();
        },

        loadTreeData: function () {
            var self = this;
            this.loadProductNode( this.productName, [], 0 ).then( function ( tree ) {
                self.container.innerHTML = self.buildTreeHTML( tree );
            }, function () {
                self.container.innerHTML = '<p class="traceability-error">Error loading traceability data.</p>';
            } );
        },

        loadProductNode: function ( productName, ancestors, depth ) {
            var self = this;
            var path = ancestors.concat( [ productName ] );
            var node = { name: productName, transformations: [] };

            if ( depth >= 12 || ancestors.indexOf( productName ) !== -1 ) {
                return Promise.resolve( node );
            }

            return new mw.Api().get( {
                action: 'ask',
                query: '[[HasOutputProducts::' + productName +
                    ']]|?TransformationName|?TransformationType|?HasInputProducts',
                format: 'json'
            } ).then( function ( data ) {
                var results = data && data.query && data.query.results || {};
                var tasks = Object.keys( results ).map( function ( pageTitle ) {
                    var result = results[ pageTitle ];
                    var printouts = result.printouts || {};
                    var inputs = self.extractPageNames( printouts.HasInputProducts );
                    var transformation = {
                        name: self.extractValue( printouts.TransformationName ) || pageTitle,
                        pageTitle: pageTitle,
                        type: self.extractValue( printouts.TransformationType ),
                        inputs: []
                    };

                    return Promise.all( inputs.map( function ( inputName ) {
                        return self.loadProductNode( inputName, path, depth + 1 );
                    } ) ).then( function ( inputNodes ) {
                        transformation.inputs = inputNodes;
                        node.transformations.push( transformation );
                    } );
                } );

                return Promise.all( tasks ).then( function () {
                    return node;
                } );
            } );
        },

        extractPageNames: function ( values ) {
            if ( !Array.isArray( values ) ) {
                return [];
            }
            return values.map( function ( value ) {
                return typeof value === 'string' ? value : value.fulltext;
            } ).filter( Boolean );
        },

        extractValue: function ( values ) {
            if ( !Array.isArray( values ) || !values.length ) {
                return '';
            }
            var value = values[ 0 ];
            return typeof value === 'string' ? value : value.fulltext || '';
        },

        buildTreeHTML: function ( treeData ) {
            var html = '<div class="traceability-tree">';
            html += this.buildProductNode( treeData );
            if ( !treeData.transformations.length ) {
                html += '<p>No transformations found for this product.</p>';
            }
            html += '</div>';
            return html;
        },

        buildProductNode: function ( product ) {
            var html = '<div class="traceability-product-branch">';
            html += '<a class="traceability-node traceability-node-link traceability-product" href="' +
                this.escapeHtml( mw.util.getUrl( product.name ) ) + '">';
            html += '<span class="traceability-node-name">' + this.escapeHtml( product.name ) + '</span>';
            html += '<div class="traceability-node-details">Product</div></a>';

            if ( product.transformations.length ) {
                html += '<div class="traceability-children">';
                product.transformations.forEach( function ( transformation ) {
                    html += this.buildTransformationNode( transformation );
                }, this );
                html += '</div>';
            }
            html += '</div>';
            return html;
        },

        buildTransformationNode: function ( transformation ) {
            var html = '<a class="traceability-node traceability-node-link traceability-transformation" href="' +
                this.escapeHtml( mw.util.getUrl( transformation.pageTitle ) ) + '">';
            html += '<span class="traceability-node-name">' + this.escapeHtml( transformation.name ) + '</span>';
            html += '<div class="traceability-node-details">Transformation' +
                ( transformation.type ? ': ' + this.escapeHtml( transformation.type ) : '' ) +
                '</div></a>';
            if ( transformation.inputs.length ) {
                html += '<div class="traceability-children traceability-inputs">';
                transformation.inputs.forEach( function ( product ) {
                    html += this.buildProductNode( product );
                }, this );
                html += '</div>';
            }
            return html;
        },

        escapeHtml: function ( text ) {
            var div = document.createElement( 'div' );
            div.textContent = text;
            return div.innerHTML;
        }
    };

    mw.WikiTraceability = {
        initTree: function ( containerId, productName ) {
            new TraceabilityTree( containerId, productName );
        },

        initOpenFoodFactsLookup: function () {
            var searchForm = document.getElementById( 'wikitraceability-off-search' );
            var searchInput = document.getElementById( 'wikitraceability-off-search-term' );
            var results = document.getElementById( 'wikitraceability-off-results' );

            if ( !searchForm || !searchInput || !results ) {
                return;
            }
            if ( searchForm.wikitraceabilityOffSearchBound ) {
                return;
            }
            searchForm.wikitraceabilityOffSearchBound = true;
            var searchButton = searchForm.querySelector( 'button[type="submit"]' );
            var nextSearchAt = 0;

            searchForm.addEventListener( 'submit', function ( event ) {
                event.preventDefault();
                var term = searchInput.value.trim();
                if ( term.length < 2 ) {
                    return;
                }
                if ( Date.now() < nextSearchAt ) {
                    results.textContent = 'Please wait a few seconds before searching again.';
                    return;
                }

                nextSearchAt = Date.now() + 6000;
                searchButton.disabled = true;
                results.textContent = 'Searching Open Food Facts...';
                new mw.Api().get( {
                    action: 'wikitraceabilityopenfoodfacts',
                    search: term
                } ).then( function ( data ) {
                    var apiResult = data && data.wikitraceabilityopenfoodfacts;
                    var products = apiResult && Array.isArray( apiResult.products ) ?
                        apiResult.products : [];
                    results.textContent = '';
                    if ( !products.length ) {
                        results.textContent = 'No matching products found.';
                    } else {
                        var list = document.createElement( 'ul' );
                        products.forEach( function ( product ) {
                            var item = document.createElement( 'li' );
                            var button = document.createElement( 'button' );
                            button.type = 'button';
                            button.className = 'wikitraceability-off-result';
                            button.textContent = product.product_name +
                                ( product.brands ? ' — ' + product.brands : '' ) +
                                ( product.quantity ? ' (' + product.quantity + ')' : '' ) +
                                ( product.code ? ' · ' + product.code : '' );
                            button.addEventListener( 'click', function () {
                                mw.WikiTraceability.importOpenFoodFactsProduct( product );
                                results.textContent = product.product_name + ' selected.';
                            } );
                            item.appendChild( button );
                            list.appendChild( item );
                        } );
                        results.appendChild( list );
                    }
                }, function () {
                    results.textContent = 'Could not search Open Food Facts. Please try again later.';
                } ).then( function () {
                    window.setTimeout( function () {
                        searchButton.disabled = false;
                    }, Math.max( 0, nextSearchAt - Date.now() ) );
                } );
            } );
        },

        importOpenFoodFactsProduct: function ( product ) {
            document.getElementById( 'wpProductName' ).value = product.product_name || '';
            document.getElementById( 'wpProductBrand' ).value = product.brands || '';
            document.getElementById( 'wpProductBarcode' ).value = product.code || '';
            document.getElementById( 'wpProductSource' ).value = 'Open Food Facts';
            document.getElementById( 'wpProductCategory' ).value = product.categories || '';
            document.getElementById( 'wpProductDescription' ).value = product.ingredients_text || '';

            var quantity = ( product.quantity || '' ).match( /^\s*(\d+(?:[.,]\d+)?)\s*(kg|g|mg|lbs?|oz)\b/i );
            if ( quantity ) {
                var unit = quantity[2].toLowerCase();
                var weight = parseFloat( quantity[1].replace( ',', '.' ) );
                if ( unit === 'mg' ) {
                    weight /= 1000;
                    unit = 'g';
                } else if ( unit === 'lb' ) {
                    unit = 'lbs';
                }
                if ( [ 'kg', 'g', 'lbs', 'oz' ].indexOf( unit ) !== -1 ) {
                    document.getElementById( 'wpProductWeight' ).value = weight;
                    document.getElementById( 'wpProductWeightUnit' ).value = unit;
                }
            }
        },

        initAllTrees: function () {
            var containers = document.querySelectorAll( '.wikitraceability-tree-container' );
            Array.prototype.forEach.call( containers, function ( container ) {
                var productName = container.getAttribute( 'data-product' );
                if ( productName ) {
                    new TraceabilityTree( container, productName );
                }
            } );
        }
    };

    $( document ).ready( function () {
        mw.WikiTraceability.initAllTrees();
        mw.WikiTraceability.initOpenFoodFactsLookup();
    } );

    if ( mw.loader ) {
        mw.loader.using( 'ext.wikitraceability', function () {
            mw.WikiTraceability.initAllTrees();
            mw.WikiTraceability.initOpenFoodFactsLookup();
        } );
    }

}( jQuery, mediaWiki ) );
