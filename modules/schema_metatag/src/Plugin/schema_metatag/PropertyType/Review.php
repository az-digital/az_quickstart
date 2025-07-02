<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Review' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "review",
 *   label = @Translation("Review"),
 *   tree_parent = {
 *     "Review",
 *   },
 *   tree_depth = 0,
 *   property_type = "Review",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "reviewBody" = {
 *       "id" = "text",
 *       "label" = @Translation("reviewBody"),
 *       "description" = @Translation("The actual body of the review."),
 *     },
 *     "datePublished" = {
 *       "id" = "date",
 *       "label" = @Translation("datePublished"),
 *       "description" = @Translation("The actual body of the review."),
 *     },
 *     "author" = {
 *       "id" = "organization",
 *       "label" = @Translation("author"),
 *       "description" = @Translation("The author of this review."),
 *       "tree_parent" = {
 *         "Organization",
 *         "Person",
 *       },
 *       "tree_depth" = 0,
 *     },
 *     "reviewRating" = {
 *       "id" = "aggregate_rating",
 *       "label" = @Translation("reviewRating"),
 *       "description" = @Translation("The rating of this review."),
 *       "tree_parent" = {
 *         "Rating",
 *         "AggregateRating",
 *       },
 *       "tree_depth" = 0,
 *     },
 *   },
 * )
 */
class Review extends PropertyTypeBase {

}
