<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_book",
 *   label = @Translation("Schema Metatag Test Book"),
 *   name = "book",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "book",
 *   tree_parent = {
 *     "Book",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestBook extends SchemaNameBase {

}
