<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'same_as' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_college_or_university_same_as",
 *   label = @Translation("same_as"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. URL of a reference Web page that unambiguously indicates the College or University's identity."),
 *   name = "same_as",
 *   group = "schema_college_or_university",
 *   weight = -20,
 *   type = "string",
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SchemaCollegeOrUniversitySameAs extends SchemaNameBase {

}
