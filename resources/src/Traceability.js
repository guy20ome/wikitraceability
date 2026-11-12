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
            var html = '<div class="traceability-node traceability-product">';
            html += '<span class="traceability-node-name">' + this.escapeHtml( product.name ) + '</span>';
            html += '<div class="traceability-node-details">Product</div></div>';

            if ( product.transformations.length ) {
                html += '<div class="traceability-children">';
                product.transformations.forEach( function ( transformation ) {
                    html += this.buildTransformationNode( transformation );
                }, this );
                html += '</div>';
            }
            return html;
        },

        buildTransformationNode: function ( transformation ) {
            var html = '<div class="traceability-node traceability-transformation">';
            html += '<span class="traceability-node-name">' + this.escapeHtml( transformation.name ) + '</span>';
            html += '<div class="traceability-node-details">Transformation' +
                ( transformation.type ? ': ' + this.escapeHtml( transformation.type ) : '' ) +
                '</div></div>';
            if ( transformation.inputs.length ) {
                html += '<div class="traceability-children">';
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
    } );

    if ( mw.loader ) {
        mw.loader.using( 'ext.wikitraceability', function () {
            mw.WikiTraceability.initAllTrees();
        } );
    }

}( jQuery, mediaWiki ) );
