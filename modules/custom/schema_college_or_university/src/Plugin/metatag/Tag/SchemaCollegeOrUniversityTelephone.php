<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_college_or_university_telephone' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_college_or_university_telephone",
 *   label = @Translation("telephone"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. A business phone number meant to be the primary contact method for customers. Be sure to include the country code and area code in the phone number."),
 *   name = "telephone",
 *   group = "schema_college_or_university",
 *   weight = 1.1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaCollegeOrUniversityTelephone extends SchemaNameBase {

}
