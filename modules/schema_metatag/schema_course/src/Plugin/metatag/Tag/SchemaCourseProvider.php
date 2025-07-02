<?php

namespace Drupal\schema_course\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_course_provider' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_course_provider",
 *   label = @Translation("provider"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The provider of the course."),
 *   name = "provider",
 *   group = "schema_course",
 *   weight = -35,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "organization",
 *   tree_parent = {
 *     "Organization",
 *   },
 *   tree_depth = 2,
 * )
 */
class SchemaCourseProvider extends SchemaNameBase {

}
