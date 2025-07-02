<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_aggregate_rating",
 *   label = @Translation("Schema Metatag Test AggregateRating"),
 *   name = "aggregateRating",
 *   description = @Translation("Test AggregateRating"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "aggregate_rating",
 *   tree_parent = {
 *     "AggregateRating",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestAggregateRating extends SchemaNameBase {

}
