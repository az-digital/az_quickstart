<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for 'OpeningHoursSpecification' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "opening_hours_specification",
 *   label = @Translation("OpeningHoursSpecification"),
 *   tree_parent = {
 *     "OpeningHoursSpecification",
 *   },
 *   tree_depth = -1,
 *   property_type = "OpeningHoursSpecification",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "dayOfWeek" = {
 *       "id" = "text",
 *       "label" = @Translation("dayOfWeek"),
 *       "description" = @Translation("Comma-separated list of the names of the days of the week."),
 *     },
 *     "opens" = {
 *       "id" = "text",
 *       "label" = @Translation("opens"),
 *       "description" = @Translation("Matching comma-separated list of the time the business location opens each day, in hh:mm:ss format."),
 *     },
 *     "closes" = {
 *       "id" = "text",
 *       "label" = @Translation("closes"),
 *       "description" = @Translation("Matching comma-separated list of the time the business location closes each day, in hh:mm:ss format."),
 *     },
 *     "validFrom" = {
 *       "id" = "date",
 *       "label" = @Translation("validFrom"),
 *       "description" = @Translation("The date of a seasonal business closure."),
 *     },
 *     "validThrough" = {
 *       "id" = "date",
 *       "label" = @Translation("validThrough"),
 *       "description" = @Translation("The date of a seasonal business closure."),
 *     },
 *   },
 * )
 */
class OpeningHoursSpecification extends PropertyTypeBase {

}
