<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_place",
 *   label = @Translation("Schema Metatag Test Place"),
 *   name = "place",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "place",
 *   tree_parent = {
 *     "Place",
 *   },
 *   tree_depth = 2,
 * )
 */
class SchemaMetatagTestPlace extends SchemaNameBase {

}
