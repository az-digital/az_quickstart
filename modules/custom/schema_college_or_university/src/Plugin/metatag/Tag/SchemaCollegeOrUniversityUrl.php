<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'url' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_college_or_university_url",
 *   label = @Translation("url"),
 *   description = @Translation("The url of the item."),
 *   name = "url",
 *   group = "schema_college_or_university",
 *   weight = -40,
 *   type = "string",
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SchemaCollegeOrUniversityUrl extends SchemaNameBase {

}