'''Product Definition Template'''

Defines a product in the traceability system. Products are linked through Transformations which specify input/output relationships.

== Product Properties ==
{| class="wikitable"
|-
! Field !! Type !! SMW Property !! Description
|-
| Name || String || Property:ProductName || Product identifier
|-
| Weight || Number || Property:ProductWeight || Base weight value
|-
| Weight Unit || String || Property:ProductWeightUnit || Unit (kg, g, lbs, etc.)
|-
| Description || Text || Property:ProductDescription || Detailed product description
|-
| Price || Currency || Property:ProductPrice || Product price in USD
|-
| Category || String || Property:ProductCategory || Product classification
|-
| Has Transformation || Page || Property:HasTransformation || Links to Transformation pages this product participates in
|}