# wikitraceability

WikiTraceability

Overview
The open traceability project aims to propose a simple web application allowing anyone
to register a product and its origination with all the transformation in between..
Description
1. Product: The product’s attributes will be defined step by step. The minimal information needed is the name, the
weight & weight unit, the description
2. Transformation: The transformation’s attributes will be defined step by step. The minimal information needed is the
quantity “in” given in the weight unit of the predecessor per predecessor unit, the transformation type and the quantity
“out” given in the weight unit of the successor per successor unit.
3. Define the traceability tree: The UI should allow to create/update/delete a product with all its attributes. It should
allow the creation of the needed preceding products and successor products as well as the transformations that are
involved.
4. Navigate the traceability tree: From one product, the UI will display partly or fully the traceability tree, allowing the
user to zoom in out,

Milestones for POC V1.0
1. DB model definition
The DB model will allow to record
1. A product with all its attributes.
2. A transformation with all its attributes
3. The link between the product and the transformation.
2. Data entry
The UI should allow to enter
1. the product’s attributes.
2. from 2 products, the transformation that applies between the 2 with all the attributes.
3. Navigation
Once a traceability tree is created, the UI allows users to navigate between products easily.
Technical constraints

Technical constraint
wikitraceability will be buit on top of wikimedia.
1. a wikimedia extension should be used to display graphically the traceability of a product. it will let the user navigate in the graph.
2. Semantic MediaWiki will be used to store all the data needed
3. wikitraceability product can be linked to an open product database like open food fact 

Scaffold
A minimal MediaWiki extension scaffold has been added with:
- `extension.json`
- `includes/SpecialProduct.php`
- `includes/SpecialTransformation.php`
- `resources/src/Traceability.js`
- `resources/styles/Traceability.css`
- `i18n/en.json`
- `i18n/WikiTraceability.alias.php`

## Product search

The wiki homepage opens the Product Search page. Users can search products already saved in the local wiki or search Open Food Facts by product name or barcode via its Search-a-licious API. Open Food Facts searches are explicit (not performed while typing); selecting a result fills the product name, brand, barcode, quantity, category, and ingredient description. Review the community-contributed data before creating the wiki product. Price is not provided by this lookup.

Products imported from Open Food Facts retain source and ODbL metadata, including a product link when a barcode is available. The lookup is read-only and uses the free Open Food Facts API; searches are rate-limited by the service, so avoid repeated searches.

Open Food Facts asks application developers to describe their integration in its [API usage form](https://docs.google.com/forms/d/e/1FAIpQLSdIE3D8qvjC_zRJw1W8OmuHhsWJ_NSckiiniAHlfaVwUZCziQ/viewform).

Next step: install the extension into a MediaWiki site and wire Semantic MediaWiki data types for Product and Transformation.
