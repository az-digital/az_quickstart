<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_review",
 *   label = @Translation("Schema Metatag Test Review"),
 *   name = "review",
 *   description = @Translation("Test Review"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "review",
 *   tree_parent = {
 *     "Review",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestReview extends SchemaNameBase {

}
