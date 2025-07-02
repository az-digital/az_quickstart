<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_number",
 *   label = @Translation("Schema Metatag Test Number"),
 *   name = "number",
 *   description = @Translation("Test Number element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "number",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestNumber extends SchemaNameBase {

}
