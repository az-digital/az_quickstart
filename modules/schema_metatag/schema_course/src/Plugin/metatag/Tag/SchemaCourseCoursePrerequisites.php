<?php

namespace Drupal\schema_course\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_course_course_prerequisites' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_course_course_prerequisites",
 *   label = @Translation("coursePrerequisites"),
 *   description = @Translation("Requirements for taking the Course."),
 *   name = "coursePrerequisites",
 *   group = "schema_course",
 *   weight = -25,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaCourseCoursePrerequisites extends SchemaNameBase {

}
