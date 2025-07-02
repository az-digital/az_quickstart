<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'MonetaryAmount' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "monetary_amount",
 *   label = @Translation("MonetaryAmount"),
 *   tree_parent = {
 *     "MonetaryAmount",
 *   },
 *   tree_depth = -1,
 *   property_type = "MonetaryAmount",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "currency" = {
 *       "id" = "text",
 *       "label" = @Translation("currency"),
 *       "description" = @Translation("The currency in which the monetary amount is expressed. Use 3-letter ISO 4217 format."),
 *     },
 *     "value" = {
 *       "id" = "quantitative_value",
 *       "label" = @Translation("value"),
 *       "description" = @Translation("The numeric value of the amount."),
 *       "tree_parent" = {
 *         "QuantitativeValue",
 *       },
 *       "tree_depth" = 0,
 *     },
 *   },
 * )
 */
class MonetaryAmount extends PropertyTypeBase {

}
