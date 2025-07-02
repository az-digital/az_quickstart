<?php

namespace Drupal\schema_organization\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_organization_review' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_review",
 *   label = @Translation("review"),
 *   description = @Translation("Reviews of this organization."),
 *   name = "review",
 *   group = "schema_organization",
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
class SchemaOrganizationReview extends SchemaNameBase {

}
