<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_college_or_university_id' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_college_or_university_id",
 *   label = @Translation("@id"),
 *   description = @Translation("REQUIRED BY GOOGLE. Globally unique @id, usually a url, used to to link other properties to this object."),
 *   name = "@id",
 *   group = "schema_college_or_university",
 *   weight = -1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaCollegeOrUniversityId extends SchemaNameBase {

}
