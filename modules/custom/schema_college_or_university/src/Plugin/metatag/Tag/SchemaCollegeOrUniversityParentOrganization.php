<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_college_or_university_parent_organization' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_college_or_university_parent_organization",
 *   label = @Translation("parentOrganization"),
 *   description = @Translation("An Organization (or ProgramMembership) to which this Organization belongs."),
 *   name = "parentOrganization",
 *   group = "schema_college_or_university",
 *   weight = 10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "program_membership",
 *   tree_parent = {
 *     "ProgramMembership",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaCollegeOrUniversityParentOrganization extends SchemaNameBase {

}
