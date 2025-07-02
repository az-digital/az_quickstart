<?php

namespace Drupal\schema_review\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_review_review_rating' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_review_review_rating",
 *   label = @Translation("reviewRating"),
 *   description = @Translation("reviewRating."),
 *   name = "reviewRating",
 *   group = "schema_review",
 *   weight = 11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "rating",
 *   tree_parent = {
 *     "Rating",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaReviewReviewRating extends SchemaNameBase {

}
