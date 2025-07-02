<?php

namespace Drupal\schema_person\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_person_family_name' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_person_family_name",
 *   label = @Translation("familyName"),
 *   description = @Translation("Family name. In the U.S., the last name of an Person. This can be used along with givenName instead of the name property."),
 *   name = "familyName",
 *   group = "schema_person",
 *   weight = -6,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaPersonFamilyName extends SchemaNameBase {

}
