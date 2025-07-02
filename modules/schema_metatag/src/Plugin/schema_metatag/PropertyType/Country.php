<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Country' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "country",
 *   label = @Translation("Country"),
 *   tree_parent = {
 *     "Country",
 *   },
 *   tree_depth = 0,
 *   property_type = "Country",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "name" = {
 *       "id" = "text",
 *       "label" = @Translation("name"),
 *       "description" = @Translation("The country. For example, USA. You can also provide the two-letter ISO 3166-1 alpha-2 country code."),
 *     },
 *   },
 * )
 */
class Country extends PropertyTypeBase {

}
