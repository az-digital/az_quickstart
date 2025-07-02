<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_brand",
 *   label = @Translation("Schema Metatag Test Brand"),
 *   name = "brand",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "brand",
 *   tree_parent = {
 *     "Brand",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestBrand extends SchemaNameBase {

}
