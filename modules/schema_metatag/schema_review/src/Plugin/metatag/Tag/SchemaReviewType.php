<?php

namespace Drupal\schema_review\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_review_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of review."),
 *   name = "@type",
 *   group = "schema_review",
 *   weight = -10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "Review",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaReviewType extends SchemaNameBase {

}
