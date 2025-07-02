<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Place' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "place",
 *   label = @Translation("Place"),
 *   tree_parent = {
 *     "Place",
 *   },
 *   tree_depth = 2,
 *   property_type = "Place",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "name" = {
 *       "id" = "text",
 *       "label" = @Translation("name"),
 *       "description" = @Translation("The name of the place."),
 *     },
 *     "url" = {
 *       "id" = "url",
 *       "label" = @Translation("url"),
 *       "description" = @Translation("The url of the place."),
 *     },
 *     "address" = {
 *       "id" = "postal_address",
 *       "label" = @Translation("address"),
 *       "description" = @Translation("The address of the place."),
 *       "tree_parent" = {
 *         "PostalAddress",
 *       },
 *       "tree_depth" = 0,
 *     },
 *     "geo" = {
 *       "id" = "geo_coordinates",
 *       "label" = @Translation("geo"),
 *       "description" = @Translation("The geo coordinates of the place."),
 *       "tree_parent" = {
 *         "GeoCoordinates",
 *       },
 *       "tree_depth" = 0,
 *     },
 *   },
 * )
 */
class Place extends PropertyTypeBase {

}
