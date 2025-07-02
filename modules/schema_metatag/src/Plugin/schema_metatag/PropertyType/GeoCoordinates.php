<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'GeoCoordinates' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "geo_coordinates",
 *   label = @Translation("GeoCoordinates"),
 *   tree_parent = {
 *     "GeoCoordinates",
 *   },
 *   tree_depth = -1,
 *   property_type = "GeoCoordinates",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "latitude" = {
 *       "id" = "text",
 *       "label" = @Translation("latitude"),
 *       "description" = @Translation("The latitude of a location. For example 37.42242 (WGS 84)."),
 *     },
 *     "longitude" = {
 *       "id" = "text",
 *       "label" = @Translation("longitude"),
 *       "description" = @Translation("The longitude of a location. For example -122.08585 (WGS 84)."),
 *     },
 *   },
 * )
 */
class GeoCoordinates extends PropertyTypeBase {

}
