<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_college_or_university_same_as' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_college_or_university_same_as",
 *   label = @Translation("sameAs"),
 *   description = @Translation("Url linked to the web site, such as wikipedia page or social profiles."),
 *   name = "sameAs",
 *   group = "schema_college_or_university",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaCollegeOrUniversitySameAs extends SchemaNameBase {

}
