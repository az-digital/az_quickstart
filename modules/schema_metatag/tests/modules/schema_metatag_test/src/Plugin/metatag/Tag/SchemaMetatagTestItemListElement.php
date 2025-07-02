<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_item_list_element",
 *   label = @Translation("Schema Metatag Test ItemListElement"),
 *   name = "itemListElement",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "item_list_element",
 *   tree_parent = {
 *     "ItemListElement",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestItemListElement extends SchemaNameBase {

}
