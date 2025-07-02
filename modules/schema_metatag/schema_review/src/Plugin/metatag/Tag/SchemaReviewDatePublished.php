<?php

namespace Drupal\schema_review\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'datePublished' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_review_date_published",
 *   label = @Translation("datePublished"),
 *   description = @Translation("Date of the review."),
 *   name = "datePublished",
 *   group = "schema_review",
 *   weight = 6,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "date",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaReviewDatePublished extends SchemaNameBase {

}
