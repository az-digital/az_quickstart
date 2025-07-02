<?php

namespace Drupal\schema_organization\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Plugin for the 'schema_organization_additional_type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_additional_type",
 *   label = @Translation("additionalType"),
 *   description = @Translation(" An additional type for the item, typically used for adding more specific types from external vocabularies in microdata syntax. This is a relationship between something and a class that the thing is in."),
 *   name = "additionalType",
 *   group = "schema_organization",
 *   weight = -4,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "Organization",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaOrganizationAdditionalType extends SchemaNameBase {

}
