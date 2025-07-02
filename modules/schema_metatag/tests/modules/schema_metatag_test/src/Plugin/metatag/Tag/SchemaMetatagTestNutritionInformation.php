<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_nutrition_information",
 *   label = @Translation("Schema Metatag Test NutritionInformation"),
 *   name = "nutritionInformation",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "nutrition_information",
 *   tree_parent = {
 *     "NutritionInformation",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestNutritionInformation extends SchemaNameBase {

}
