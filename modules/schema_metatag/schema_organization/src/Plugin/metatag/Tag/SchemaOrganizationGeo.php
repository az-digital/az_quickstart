<?php

namespace Drupal\schema_organization\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_organization_geo' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_geo",
 *   label = @Translation("geo"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The geographic coordinates of a place or event."),
 *   name = "geo",
 *   group = "schema_organization",
 *   weight = 10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "geo_coordinates",
 *   tree_parent = {
 *     "GeoCoordinates",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaOrganizationGeo extends SchemaNameBase {

}
