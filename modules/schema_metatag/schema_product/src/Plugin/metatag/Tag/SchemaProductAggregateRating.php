<?php

namespace Drupal\schema_product\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_product_aggregate_rating' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_product_aggregate_rating",
 *   label = @Translation("aggregateRating"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The overall rating, based on a collection of reviews or ratings, of the item."),
 *   name = "aggregateRating",
 *   group = "schema_product",
 *   weight = 11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "aggregate_rating",
 *   tree_parent = {
 *     "AggregateRating",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaProductAggregateRating extends SchemaNameBase {

}
