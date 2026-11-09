( function ( $, mw ) {
    'use strict';

    if ( typeof mw === 'undefined' || typeof $ === 'undefined' ) {
        return;
    }

    console.log( 'WikiTraceability extension loaded.' );

    var TraceabilityTree = function ( containerId, productName ) {
        this.container = document.getElementById( containerId );
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
            var api = new mw.Api();

            var query = {
                action: 'ask',
                query: '[[' + encodeURIComponent( this.productName ) + ']]|?HasTransformation|?HasInputProducts|?HasOutputProducts',
                format: 'json'
            };

            api.get( query ).done( function ( data ) {
                self.renderTree( data );
            } ).fail( function ( error ) {
                self.container.innerHTML = '<p class="traceability-error">Error loading traceability data: ' + error + '</p>';
            } );
        },

        renderTree: function ( data ) {
            var treeData = this.parseSMWData( data );
            var html = this.buildTreeHTML( treeData );
            this.container.innerHTML = html;
            this.attachEventHandlers();
        },

        parseSMWData: function ( data ) {
            var results = [];
            if ( data && data.query && data.query.results ) {
                var product = data.query.results[ this.productName ];
                if ( product ) {
                    results = this.extractTransformations( product );
                }
            }
            return { name: this.productName, transformations: results };
        },

        extractTransformations: function ( product ) {
            var transformations = [];
            if ( product.printouts ) {
                var hasTransformation = product.printouts[ 'HasTransformation' ];
                if ( hasTransformation ) {
                    for ( var i = 0; i < hasTransformation.length; i++ ) {
                        transformations.push( hasTransformation[ i ] );
                    }
                }
            }
            return transformations;
        },

        buildTreeHTML: function ( treeData ) {
            var html = '<div class="traceability-tree">';
            html += '<div class="traceability-node traceability-product">';
            html += '<span class="traceability-node-name">' + this.escapeHtml( treeData.name ) + '</span>';
            html += '<div class="traceability-node-details">Product</div>';
            html += '</div>';

            if ( treeData.transformations && treeData.transformations.length > 0 ) {
                html += '<div class="traceability-children">';
                for ( var i = 0; i < treeData.transformations.length; i++ ) {
                    html += this.buildTransformationNode( treeData.transformations[ i ] );
                }
                html += '</div>';
            }

            html += '</div>';
            return html;
        },

        buildTransformationNode: function ( transformation ) {
            var html = '<div class="traceability-node traceability-transformation">';
            html += '<span class="traceability-node-name">' + this.escapeHtml( transformation ) + '</span>';
            html += '<div class="traceability-node-details">Transformation</div>';
            html += '</div>';
            return html;
        },

        attachEventHandlers: function () {
            var nodes = this.container.querySelectorAll( '.traceability-node' );
            var self = this;

            Array.prototype.forEach.call( nodes, function ( node ) {
                node.addEventListener( 'click', function ( e ) {
                    if ( e.target.classList.contains( 'traceability-node-name' ) ) {
                        var name = e.target.textContent;
                        self.loadProductTree( name );
                    }
                } );
            } );
        },

        loadProductTree: function ( productName ) {
            this.productName = productName;
            this.loadTreeData();
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
                    new TraceabilityTree( container.id, productName );
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
