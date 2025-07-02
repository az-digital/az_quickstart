<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'AggregateRating' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "aggregate_rating",
 *   label = @Translation("AggregateRating"),
 *   tree_parent = {
 *     "AggregateRating",
 *   },
 *   tree_depth = 0,
 *   property_type = "AggregateRating",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "ratingValue" = {
 *       "id" = "number",
 *       "label" = @Translation("ratingValue"),
 *       "description" = @Translation("The numeric rating of the item."),
 *     },
 *     "ratingCount" = {
 *       "id" = "number",
 *       "label" = @Translation("ratingCount"),
 *       "description" = @Translation("The number of ratings included."),
 *     },
 *     "bestRating" = {
 *       "id" = "number",
 *       "label" = @Translation("bestRating"),
 *       "description" = @Translation("The highest rating value possible."),
 *     },
 *     "worstRating" = {
 *       "id" = "number",
 *       "label" = @Translation("worstRating"),
 *       "description" = @Translation("The lowest rating value possible."),
 *     },
 *   },
 * )
 */
class AggregateRating extends PropertyTypeBase {

}
