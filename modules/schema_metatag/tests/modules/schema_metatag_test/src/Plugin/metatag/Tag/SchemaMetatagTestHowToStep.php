<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_how_to_step",
 *   label = @Translation("Schema Metatag Test HowToStep"),
 *   name = "howToStep",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "how_to_step",
 *   tree_parent = {
 *     "HowToStep",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestHowToStep extends SchemaNameBase {

}
