<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'SpeakableSpecification' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "speakable_specification",
 *   label = @Translation("SpeakableSpecification"),
 *   tree_parent = {
 *     "SpeakableSpecification",
 *   },
 *   tree_depth = 0,
 *   property_type = "SpeakableSpecification",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "xpath" = {
 *       "id" = "text",
 *       "label" = @Translation("xpath"),
 *       "description" = @Translation("Separate xpaths by comma, as in: :example", arguments = {
 *         ":example" = "/html/head/title, /html/head/meta[@name='description']"
 *       }),
 *     },
 *     "cssSelector" = {
 *       "id" = "text",
 *       "label" = @Translation("cssSelector"),
 *       "description" = @Translation("Separate selectors by comma, as in: :example", arguments = {
 *         ":example" = "#title, #summary"
 *       }),
 *     },
 *   },
 * )
 */
class SpeakableSpecification extends PropertyTypeBase {

}
