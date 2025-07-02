<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_entry_point",
 *   label = @Translation("Schema Metatag Test EntryPoint"),
 *   name = "entryPoint",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "entry_point",
 *   tree_parent = {
 *     "EntryPoint",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestEntryPoint extends SchemaNameBase {

}
