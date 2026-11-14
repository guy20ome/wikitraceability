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
                html += '<p><a href="' + this.escapeHtml( mw.util.getUrl(
                    'Special:TraceabilityTransformation',
                    { wpOutputProducts: treeData.name }
                ) ) + '">No transformations found. Add a transformation for this product.</a></p>';
            }
            html += '</div>';
            return html;
        },

        buildProductNode: function ( product ) {
            var html = '<div class="traceability-product-branch">';
            var productUrl = mw.util.getUrl( product.name );
            html += '<a class="traceability-node traceability-node-link traceability-product" href="' +
                this.escapeHtml( productUrl ) + '">';
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

        initLocalProductLookup: function () {
            var searchForm = document.getElementById( 'wikitraceability-local-search' );
            var searchInput = document.getElementById( 'wikitraceability-local-search-term' );
            var results = document.getElementById( 'wikitraceability-local-results' );

            if ( !searchForm || !searchInput || !results || searchForm.wikitraceabilityLocalSearchBound ) {
                return;
            }
            searchForm.wikitraceabilityLocalSearchBound = true;
            var searchButton = searchForm.querySelector( 'button[type="submit"]' );

            searchForm.addEventListener( 'submit', function ( event ) {
                event.preventDefault();
                var term = searchInput.value.trim().replace( /[^\p{L}\p{N}\s-]/gu, '' );
                if ( term.length < 2 ) {
                    results.textContent = 'Enter at least two letters or numbers to search.';
                    return;
                }

                searchButton.disabled = true;
                results.textContent = 'Searching this wiki...';
                new mw.Api().get( {
                    action: 'ask',
                    query: '[[ProductName::+]]|?ProductName|limit=500'
                } ).then( function ( data ) {
                    var products = data && data.query && data.query.results || {};
                    var pages = Object.keys( products ).map( function ( title ) {
                        var result = products[ title ];
                        var productName = result.printouts &&
                            result.printouts.ProductName && result.printouts.ProductName[ 0 ];
                        return productName && productName.fulltext || result.fulltext || title;
                    } ).filter( function ( productName ) {
                        return productName.toLowerCase().indexOf( term.toLowerCase() ) !== -1;
                    } ).slice( 0, 10 );
                    results.textContent = '';
                    if ( !pages.length ) {
                        results.textContent = 'No matching products found in this wiki.';
                        return;
                    }

                    var list = document.createElement( 'ul' );
                    pages.forEach( function ( productName ) {
                        var item = document.createElement( 'li' );
                        var link = document.createElement( 'a' );
                        link.href = mw.util.getUrl( productName );
                        link.textContent = productName;
                        item.appendChild( link );
                        list.appendChild( item );
                    } );
                    results.appendChild( list );
                }, function () {
                    results.textContent = 'Could not search products in this wiki. Please try again.';
                } ).then( function () {
                    searchButton.disabled = false;
                } );
            } );
        },

        initTransformationProductSelectors: function () {
            var form = document.querySelector( '.wikitraceability-transformation-form' );
            var status = document.getElementById( 'wikitraceability-product-suggestions-status' );

            if ( !form || !status || form.wikitraceabilityProductSelectorsBound ) {
                return;
            }
            form.wikitraceabilityProductSelectorsBound = true;
            var catalogPromise = null;
            var products = [];

            function loadProducts() {
                if ( !catalogPromise ) {
                    catalogPromise = new mw.Api().get( {
                        action: 'ask',
                        query: '[[ProductName::+]]|?ProductName|limit=500'
                    } ).then( function ( data ) {
                        var results = data && data.query && data.query.results || {};
                        products = Object.keys( results ).map( function ( title ) {
                            var result = results[ title ];
                            var productName = result.printouts &&
                                result.printouts.ProductName && result.printouts.ProductName[ 0 ];
                            return productName && productName.fulltext || result.fulltext || title;
                        } );
                        return products;
                    }, function () {
                        catalogPromise = null;
                        throw new Error( 'Could not load products.' );
                    } );
                }
                return catalogPromise;
            }

            function updateSuggestions( input ) {
                var term = input.value.trim();
                var suggestionList = form.querySelector( '#' + input.getAttribute( 'aria-controls' ) );
                suggestionList.textContent = '';
                suggestionList.hidden = true;
                input.setAttribute( 'aria-expanded', 'false' );
                input.removeAttribute( 'aria-activedescendant' );
                if ( term.length < 3 ) {
                    status.textContent = 'Type at least 3 letters to find products.';
                    return;
                }
                status.textContent = 'Loading product suggestions...';
                loadProducts().then( function ( catalog ) {
                    if ( input.value.trim().length < 3 ) {
                        return;
                    }
                    var currentTerm = input.value.trim().toLowerCase();
                    var matches = catalog.filter( function ( productName ) {
                        return productName.toLowerCase().indexOf( currentTerm ) !== -1;
                    } ).slice( 0, 20 );
                    suggestionList.textContent = '';
                    matches.forEach( function ( productName, index ) {
                        var option = document.createElement( 'div' );
                        option.id = suggestionList.id + '-option-' + index;
                        option.className = 'wikitraceability-product-suggestion';
                        option.setAttribute( 'role', 'option' );
                        option.setAttribute( 'aria-selected', 'false' );
                        option.textContent = productName;
                        suggestionList.appendChild( option );
                    } );
                    suggestionList.hidden = !matches.length;
                    input.setAttribute( 'aria-expanded', matches.length ? 'true' : 'false' );
                    status.textContent = matches.length ?
                        'Select a matching product from the suggestions.' :
                        'No matching products found.';
                }, function () {
                    status.textContent = 'Could not load product suggestions. Please try again.';
                } );
            }

            function updateProductRows( section, direction ) {
                var rows = section.querySelector( '.wikitraceability-product-rows' );
                var productRows = Array.prototype.slice.call( rows.children );
                var label = direction === 'input' ? 'Input' : 'Output';

                productRows.forEach( function ( row, index ) {
                    var input = row.querySelector( '.wikitraceability-product-selector' );
                    var rowLabel = row.querySelector( 'label' );
                    var suggestionList = row.querySelector( '.wikitraceability-product-suggestions' );
                    var removeButton = row.querySelector( '[data-remove-product]' );
                    var inputId = 'wp' + label + 'Products-' + index;

                    input.id = inputId;
                    input.setAttribute( 'aria-controls', inputId + '-suggestions' );
                    rowLabel.htmlFor = inputId;
                    rowLabel.textContent = label + ' product ' + ( index + 1 );
                    suggestionList.id = inputId + '-suggestions';
                    suggestionList.textContent = '';
                    suggestionList.hidden = true;
                    input.setAttribute( 'aria-expanded', 'false' );
                    input.removeAttribute( 'aria-activedescendant' );
                    removeButton.setAttribute(
                        'aria-label',
                        'Remove ' + direction + ' product ' + ( index + 1 )
                    );
                    removeButton.disabled = productRows.length === 1;
                } );
            }

            form.addEventListener( 'input', function ( event ) {
                if ( event.target.classList.contains( 'wikitraceability-product-selector' ) ) {
                    updateSuggestions( event.target );
                }
            } );

            form.addEventListener( 'mousedown', function ( event ) {
                if ( event.target.closest( '.wikitraceability-product-suggestion' ) ) {
                    event.preventDefault();
                }
            } );

            form.addEventListener( 'click', function ( event ) {
                var suggestion = event.target.closest( '.wikitraceability-product-suggestion' );
                if ( suggestion ) {
                    var row = suggestion.closest( '.wikitraceability-product-row' );
                    var input = row.querySelector( '.wikitraceability-product-selector' );
                    input.value = suggestion.textContent;
                    input.setAttribute( 'aria-expanded', 'false' );
                    input.removeAttribute( 'aria-activedescendant' );
                    suggestion.parentNode.hidden = true;
                    status.textContent = 'Product selected.';
                    return;
                }
                var removeButton = event.target.closest( '[data-remove-product]' );
                if ( removeButton ) {
                    var removeSection = removeButton.closest( '[data-product-list]' );
                    var removeDirection = removeSection.getAttribute( 'data-product-list' );
                    if ( removeSection.querySelectorAll( '.wikitraceability-product-row' ).length > 1 ) {
                        removeButton.closest( '.wikitraceability-product-row' ).remove();
                        updateProductRows( removeSection, removeDirection );
                    }
                    return;
                }
                var button = event.target.closest( '[data-add-product]' );
                if ( !button ) {
                    return;
                }
                var direction = button.getAttribute( 'data-add-product' );
                var section = form.querySelector( '[data-product-list="' + direction + '"]' );
                var rows = section.querySelector( '.wikitraceability-product-rows' );
                var index = rows.children.length;
                var label = direction === 'input' ? 'Input' : 'Output';
                var input = document.createElement( 'input' );
                var row = document.createElement( 'div' );
                var rowLabel = document.createElement( 'label' );

                input.id = 'wp' + label + 'Products-' + index;
                input.name = 'wp' + label + 'Products[]';
                input.type = 'search';
                input.className = 'mw-htmlform-field wikitraceability-product-selector';
                input.autocomplete = 'off';
                input.setAttribute( 'aria-autocomplete', 'list' );
                input.setAttribute( 'aria-expanded', 'false' );
                input.setAttribute( 'aria-controls', input.id + '-suggestions' );
                input.required = true;
                row.className = 'wikitraceability-product-row';
                rowLabel.htmlFor = input.id;
                rowLabel.textContent = label + ' product ' + ( index + 1 );
                row.appendChild( rowLabel );
                row.appendChild( document.createElement( 'br' ) );
                row.appendChild( input );
                var suggestionList = document.createElement( 'div' );
                suggestionList.id = input.id + '-suggestions';
                suggestionList.className = 'wikitraceability-product-suggestions';
                suggestionList.setAttribute( 'role', 'listbox' );
                suggestionList.hidden = true;
                row.appendChild( suggestionList );
                var removeButton = document.createElement( 'button' );
                removeButton.type = 'button';
                removeButton.className = 'wikitraceability-remove-product';
                removeButton.setAttribute( 'data-remove-product', '' );
                removeButton.setAttribute( 'aria-label', 'Remove ' + direction + ' product ' + ( index + 1 ) );
                removeButton.textContent = 'Remove';
                row.appendChild( removeButton );
                rows.appendChild( row );
                updateProductRows( section, direction );
                input.focus();
            } );

            form.addEventListener( 'keydown', function ( event ) {
                var input = event.target;
                if ( !input.classList.contains( 'wikitraceability-product-selector' ) ) {
                    return;
                }
                var suggestionList = form.querySelector( '#' + input.getAttribute( 'aria-controls' ) );
                var options = Array.prototype.slice.call(
                    suggestionList.querySelectorAll( '.wikitraceability-product-suggestion' )
                );
                if ( event.key === 'Escape' ) {
                    suggestionList.hidden = true;
                    input.setAttribute( 'aria-expanded', 'false' );
                    input.removeAttribute( 'aria-activedescendant' );
                    return;
                }
                if ( !options.length || suggestionList.hidden ) {
                    return;
                }
                if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
                    event.preventDefault();
                    var activeIndex = options.findIndex( function ( option ) {
                        return option.id === input.getAttribute( 'aria-activedescendant' );
                    } );
                    activeIndex += event.key === 'ArrowDown' ? 1 : -1;
                    activeIndex = ( activeIndex + options.length ) % options.length;
                    options.forEach( function ( option, index ) {
                        option.setAttribute( 'aria-selected', index === activeIndex ? 'true' : 'false' );
                    } );
                    input.setAttribute( 'aria-activedescendant', options[ activeIndex ].id );
                } else if ( event.key === 'Enter' && input.hasAttribute( 'aria-activedescendant' ) ) {
                    event.preventDefault();
                    options.forEach( function ( option ) {
                        if ( option.id === input.getAttribute( 'aria-activedescendant' ) ) {
                            input.value = option.textContent;
                            suggestionList.hidden = true;
                            input.setAttribute( 'aria-expanded', 'false' );
                            input.removeAttribute( 'aria-activedescendant' );
                            status.textContent = 'Product selected.';
                        }
                    } );
                }
            } );

            status.textContent = 'Type at least 3 letters to find products.';
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
        mw.WikiTraceability.initLocalProductLookup();
        mw.WikiTraceability.initOpenFoodFactsLookup();
        mw.WikiTraceability.initTransformationProductSelectors();
    } );

    if ( mw.loader ) {
        mw.loader.using( 'ext.wikitraceability', function () {
            mw.WikiTraceability.initAllTrees();
            mw.WikiTraceability.initLocalProductLookup();
            mw.WikiTraceability.initOpenFoodFactsLookup();
            mw.WikiTraceability.initTransformationProductSelectors();
        } );
    }

}( jQuery, mediaWiki ) );
