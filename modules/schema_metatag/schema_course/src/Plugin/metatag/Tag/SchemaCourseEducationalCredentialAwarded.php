<?php

namespace Drupal\schema_course\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_course_educational_credential_awarded' tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_course_educational_credential_awarded",
 *   label = @Translation("educationalCredentialAwarded"),
 *   description = @Translation("A description of the qualification, award, certificate, diploma or other educational credential awarded as a consequence of successful completion of this course."),
 *   name = "educationalCredentialAwarded",
 *   group = "schema_course",
 *   weight = -20,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaCourseEducationalCredentialAwarded extends SchemaNameBase {

}
