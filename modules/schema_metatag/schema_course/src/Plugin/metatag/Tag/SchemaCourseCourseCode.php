<?php

namespace Drupal\schema_course\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_course_course_code' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_course_course_code",
 *   label = @Translation("courseCode"),
 *   description = @Translation("The identifier for the Course used by the course provider (e.g. CS101 or 6.001)."),
 *   name = "courseCode",
 *   group = "schema_course",
 *   weight = -30,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaCourseCourseCode extends SchemaNameBase {

}
