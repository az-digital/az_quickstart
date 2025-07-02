<?php

namespace Drupal\schema_organization\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_organization_sub_organization' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_sub_organization",
 *   label = @Translation("subOrganization"),
 *   description = @Translation("The smaller organization that this organization is a parentOrganization of, if any. e.g. The College of Science is a sub organization of the University of Arizona."),
 *   name = "subOrganization",
 *   group = "schema_organization",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "organization",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaOrganizationSubOrganization extends SchemaNameBase {

}
