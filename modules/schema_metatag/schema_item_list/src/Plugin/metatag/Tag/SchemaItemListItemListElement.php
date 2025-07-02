<?php

namespace Drupal\schema_item_list\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_item_list_element' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_item_list_item_list_element",
 *   label = @Translation("itemListElement"),
 *   description = @Translation("REQUIRED BY GOOGLE. "),
 *   name = "itemListElement",
 *   group = "schema_item_list",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "item_list_element",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaItemListItemListElement extends SchemaNameBase {

}
