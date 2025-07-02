<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'QuantitativeValue' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "quantitative_value",
 *   label = @Translation("QuantitativeValue"),
 *   tree_parent = {
 *     "QuantitativeValue",
 *   },
 *   tree_depth = 0,
 *   property_type = "QuantitativeValue",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "value" = {
 *       "id" = "number",
 *       "label" = @Translation("value"),
 *       "description" = @Translation("The value."),
 *     },
 *     "minValue" = {
 *       "id" = "number",
 *       "label" = @Translation("minValue"),
 *       "description" = @Translation("The minimum value."),
 *     },
 *     "maxValue" = {
 *       "id" = "number",
 *       "label" = @Translation("maxValue"),
 *       "description" = @Translation("The maximum value."),
 *     },
 *     "unitText" = {
 *       "id" = "text",
 *       "label" = @Translation("unitText"),
 *       "description" = @Translation("The unit of the value, like HOUR, DAY, WEEK, MONTH, or YEAR."),
 *     },
 *   },
 * )
 */
class QuantitativeValue extends PropertyTypeBase {

}
