<?php

namespace Drupal\schema_organization\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_organization_parent_organization' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_parent_organization",
 *   label = @Translation("parentOrganization"),
 *   description = @Translation("The larger organization that this organization is a subOrganization of, if any. e.g. The University of Arizona is the parent organization of its College of Science."),
 *   name = "parentOrganization",
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
class SchemaOrganizationParentOrganization extends SchemaNameBase {

}
