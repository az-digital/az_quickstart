<?php

namespace Drupal\schema_product\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'gtin14' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_product_gtin14",
 *   label = @Translation("gtin14"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Populate one of sku, gtin8, gtin12, gtin13, gtin14, isbn, or mpn."),
 *   name = "gtin14",
 *   group = "schema_product",
 *   weight = 7,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaProductGtin14 extends SchemaNameBase {

}
