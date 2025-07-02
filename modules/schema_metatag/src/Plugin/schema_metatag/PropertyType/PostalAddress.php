<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'PostalAddress' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "postal_address",
 *   label = @Translation("PostalAddress"),
 *   tree_parent = {
 *     "PostalAddress",
 *   },
 *   tree_depth = 0,
 *   property_type = "PostalAddress",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "streetAddress" = {
 *       "id" = "text",
 *       "label" = @Translation("streetAddress"),
 *       "description" = @Translation("The street address. For example, 1600 Amphitheatre Pkwy."),
 *     },
 *     "addressLocality" = {
 *       "id" = "text",
 *       "label" = @Translation("addressLocality"),
 *       "description" = @Translation("The locality. For example, Mountain View."),
 *     },
 *     "addressRegion" = {
 *       "id" = "text",
 *       "label" = @Translation("addressRegion"),
 *       "description" = @Translation("The region. For example, CA."),
 *     },
 *     "postalCode" = {
 *       "id" = "text",
 *       "label" = @Translation("postalCode"),
 *       "description" = @Translation("The postal code. For example, 94043."),
 *     },
 *     "addressCountry" = {
 *       "id" = "text",
 *       "label" = @Translation("addressCountry"),
 *       "description" = @Translation("The country. For example, USA. You can also provide the two-letter ISO 3166-1 alpha-2 country code."),
 *     },
 *   },
 * )
 */
class PostalAddress extends PropertyTypeBase {

}
