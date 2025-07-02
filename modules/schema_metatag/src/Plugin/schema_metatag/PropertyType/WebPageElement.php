<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'WebPageElement' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "web_page_element",
 *   label = @Translation("WebPageElement"),
 *   tree_parent = {
 *     "WebPageElement",
 *   },
 *   tree_depth = 0,
 *   property_type = "WebPageElement",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "isAccessibleForFree" = {
 *       "id" = "boolean",
 *       "label" = @Translation("isAccessibleForFree"),
 *       "description" = @Translation("True or False, whether this element is accessible for free."),
 *     },
 *     "cssSelector" = {
 *       "id" = "text",
 *       "label" = @Translation("cssSelector"),
 *       "description" = @Translation("List of class names of the parts of the web page that are not free, i.e. '.first-class', '.second-class'. Do NOT surround class names with quotation marks!"),
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
class WebPageElement extends PropertyTypeBase {

}
