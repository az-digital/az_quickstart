<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_opening_hours_specification",
 *   label = @Translation("Schema Metatag Test OpeningHoursSpecification"),
 *   name = "openingHoursSpecification",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "opening_hours_specification",
 *   tree_parent = {
 *     "OpeningHoursSpecification",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestOpeningHoursSpecification extends SchemaNameBase {

}
