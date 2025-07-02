<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'CreativeWork' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "creative_work",
 *   label = @Translation("CreativeWork"),
 *   tree_parent = {
 *     "CreativeWork",
 *   },
 *   tree_depth = 2,
 *   property_type = "CreativeWork",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "name" = {
 *       "id" = "text",
 *       "label" = @Translation("name"),
 *       "description" = @Translation("The name of the work."),
 *     },
 *     "url" = {
 *       "id" = "url",
 *       "label" = @Translation("url"),
 *       "description" = @Translation("Absolute URL of the canonical Web page for the work."),
 *     },
 *     "sameAs" = {
 *       "id" = "url",
 *       "label" = @Translation("sameAs"),
 *       "description" = @Translation("Urls and social media links, comma-separated list of absolute URLs."),
 *     },
 *     "datePublished" = {
 *       "id" = "date",
 *       "label" = @Translation("datePublished"),
 *       "description" = @Translation("Publication date."),
 *     },
 *   },
 * )
 */
class CreativeWork extends PropertyTypeBase {

}
