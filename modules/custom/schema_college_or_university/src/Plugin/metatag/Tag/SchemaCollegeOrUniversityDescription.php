<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'description' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_college_or_university_description",
 *   label = @Translation("description"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. A description of the program."),
 *   name = "description",
 *   group = "schema_college_or_university",
 *   weight = -35,
 *   type = "string",
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SchemaCollegeOrUniversityDescription extends SchemaNameBase {

}