<?php

namespace Drupal\schema_product\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'brand' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_product_brand",
 *   label = @Translation("brand"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The brand of the product."),
 *   name = "brand",
 *   group = "schema_product",
 *   weight = 10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "brand",
 *   tree_parent = {
 *     "Brand",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaProductBrand extends SchemaNameBase {

}
