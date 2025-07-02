<?php

namespace Drupal\schema_person\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_person_member_of' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_person_member_of",
 *   label = @Translation("memberOf"),
 *   description = @Translation("An Organization (or ProgramMembership) to which this Person belongs."),
 *   name = "memberOf",
 *   group = "schema_person",
 *   weight = 11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "program_membership",
 *   tree_parent = {
 *     "ProgramMembership",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaPersonMemberOf extends SchemaNameBase {

}
