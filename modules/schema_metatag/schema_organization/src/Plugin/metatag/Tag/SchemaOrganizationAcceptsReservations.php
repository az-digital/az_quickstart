<?php

namespace Drupal\schema_organization\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for 'schema_organization_accepts_reservations' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_accepts_reservations",
 *   label = @Translation("acceptsReservations"),
 *   description = @Translation("RECOMMENDED BY GOOGLE for food establishments, True or False. If True, the best practice is to also define potentialAction."),
 *   name = "acceptsReservations",
 *   group = "schema_organization",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "boolean",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaOrganizationAcceptsReservations extends SchemaNameBase {

}
