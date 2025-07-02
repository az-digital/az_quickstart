<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Rating' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "rating",
 *   label = @Translation("Rating"),
 *   tree_parent = {
 *     "Rating",
 *   },
 *   tree_depth = 0,
 *   property_type = "Rating",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "ratingValue" = {
 *       "id" = "number",
 *       "label" = @Translation("text"),
 *       "description" = @Translation("The numeric rating of the item."),
 *     },
 *     "bestRating" = {
 *       "id" = "number",
 *       "label" = @Translation("text"),
 *       "description" = @Translation("The highest rating value possible."),
 *     },
 *     "worstRating" = {
 *       "id" = "number",
 *       "label" = @Translation("text"),
 *       "description" = @Translation("The lowest rating value possible."),
 *     },
 *   },
 * )
 */
class Rating extends PropertyTypeBase {

}
