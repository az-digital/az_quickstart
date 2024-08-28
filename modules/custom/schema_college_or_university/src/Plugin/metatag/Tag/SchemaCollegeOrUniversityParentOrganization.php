<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'parent_organization' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_college_or_university_parent_organization",
 *   label = @Translation("parent_organization"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The Parent Organization of the College or University."),
 *   name = "parent_organization",
 *   group = "schema_college_or_university",
 *   weight = -35,
 *   type = "string",
 *   property_type = "organization",
 *   tree_parent = {},
 *   tree_depth = -1,
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SchemaCollegeOrUniversityParentOrganization extends SchemaNameBase {

}
