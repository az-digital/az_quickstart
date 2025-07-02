<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'EntryPoint' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "entry_point",
 *   label = @Translation("EntryPoint"),
 *   tree_parent = {
 *     "EntryPoint",
 *   },
 *   tree_depth = 0,
 *   property_type = "EntryPoint",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "urlTemplate" = {
 *       "id" = "text",
 *       "label" = @Translation("urlTemplate"),
 *       "description" = @Translation("An url template (RFC6570) that will be used to construct the target of the execution of the action, i.e. http://www.example.com/forrest_gump?autoplay=true."),
 *     },
 *     "actionPlatform" = {
 *       "id" = "text",
 *       "label" = @Translation("actionPlatform"),
 *       "description" = @Translation("Comma-separated list of the high level platform(s) where the Action can be performed for the given URL. Examples: http://schema.org/DesktopWebPlatform, http://schema.org/MobileWebPlatform, http://schema.org/IOSPlatform, http://schema.googleapis.com/GoogleVideoCast."),
 *     },
 *     "inLanguage" = {
 *       "id" = "text",
 *       "label" = @Translation("inLanguage"),
 *       "description" = @Translation("The BCP-47 language code of this item, e.g. 'ja' is Japanese, or 'en-US' for American English."),
 *     },
 *   },
 * )
 */
class EntryPoint extends PropertyTypeBase {

}
