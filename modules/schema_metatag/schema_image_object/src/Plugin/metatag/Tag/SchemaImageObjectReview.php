<?php

namespace Drupal\schema_image_object\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_image_object_review' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_image_object_review",
 *   label = @Translation("review"),
 *   description = @Translation("Review."),
 *   name = "review",
 *   group = "schema_image_object",
 *   weight = 11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "review",
 *   tree_parent = {
 *     "Review",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaImageObjectReview extends SchemaNameBase {

}
