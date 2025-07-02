<?php

namespace Drupal\schema_organization\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_organization_potential_action' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_potential_action",
 *   label = @Translation("potentialAction"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Potential action provided by this organization."),
 *   name = "potentialAction",
 *   group = "schema_organization",
 *   weight = 15,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "action",
 *   tree_parent = {
 *     "OrderAction",
 *     "ReserveAction",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaOrganizationPotentialAction extends SchemaNameBase {

}
