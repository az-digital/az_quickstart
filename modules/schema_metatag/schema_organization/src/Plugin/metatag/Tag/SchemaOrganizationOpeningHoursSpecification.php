<?php

namespace Drupal\schema_organization\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for 'schema_organization_opening_hours_specification' tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_opening_hours_specification",
 *   label = @Translation("openingHoursSpecification"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Hours during which the business location is open."),
 *   name = "openingHoursSpecification",
 *   group = "schema_organization",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "opening_hours_specification",
 *   tree_parent = {
 *     "OpeningHoursSpecification",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaOrganizationOpeningHoursSpecification extends SchemaNameBase {

}
