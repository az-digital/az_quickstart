<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_college_or_university_type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_college_or_university_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of college_or_university."),
 *   name = "@type",
 *   group = "schema_college_or_university",
 *   weight = -10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "Organization",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaCollegeOrUniversityType extends SchemaNameBase {

}
