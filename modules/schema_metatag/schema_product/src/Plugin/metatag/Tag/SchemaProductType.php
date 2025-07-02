<?php

namespace Drupal\schema_product\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_product_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of product."),
 *   name = "@type",
 *   group = "schema_product",
 *   weight = -10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "Product",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaProductType extends SchemaNameBase {

}
