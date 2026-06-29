'''Transformation Definition Template'''

Defines how products are linked through input/output relationships. A transformation can have multiple products as inputs (predecessors) and multiple products as outputs (successors).

== Transformation Properties ==
{| class="wikitable"
|-
! Field !! Type !! SMW Property !! Description
|-
| Name || String || Property:TransformationName || Transformation identifier
|-
| Type || String || Property:TransformationType || Processing type (mixing, refining, assembly, etc.)
|-
| Input Quantity || Number || Property:InputQuantityPerUnit || Quantity of input products per unit of predecessor
|-
| Output Quantity || Number || Property:OutputQuantityPerUnit || Quantity of output products per unit of successor
|-
| Input Products || Product:Page || Property:HasInputProducts || Array of products used as inputs (predecessors)
|-
| Output Products || Product:Page || Property:HasOutputProducts || Array of products generated as outputs (successors)
|}