<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_creative_work_season",
 *   label = @Translation("Schema Metatag Test CreativeWorkSeason"),
 *   name = "creativeWorkSeason",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "creative_work_season",
 *   tree_parent = {
 *     "CreativeWorkSeason",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestCreativeWorkSeason extends SchemaNameBase {

}
