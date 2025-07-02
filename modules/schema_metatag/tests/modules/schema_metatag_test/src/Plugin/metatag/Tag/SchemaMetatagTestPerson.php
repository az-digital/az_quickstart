<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_person",
 *   label = @Translation("Schema Metatag Test Person"),
 *   name = "person",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "person",
 *   tree_parent = {
 *     "Person",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestPerson extends SchemaNameBase {

}
