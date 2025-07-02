<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_quantitative_value",
 *   label = @Translation("Schema Metatag Test QuantitativeValue"),
 *   name = "quantitativeValue",
 *   description = @Translation("Test QuantitativeValue element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "quantitative_value",
 *   tree_parent = {
 *     "QuantitativeValue",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestQuantitativeValue extends SchemaNameBase {

}
