<?php

namespace Drupal\schema_organization\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_organization_member_of' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_member_of",
 *   label = @Translation("memberOf"),
 *   description = @Translation("An Organization (or ProgramMembership) to which this Organization belongs."),
 *   name = "memberOf",
 *   group = "schema_organization",
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
class SchemaOrganizationMemberOf extends SchemaNameBase {

}
