<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for 'Person' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "person",
 *   label = @Translation("Person"),
 *   tree_parent = {
 *     "Person",
 *   },
 *   tree_depth = 2,
 *   property_type = "Person",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "@id" = {
 *       "id" = "text",
 *       "label" = @Translation("@id"),
 *       "description" = @Translation("Globally unique @id of the person or organization, usually a url, used to to link other properties to this object."),
 *     },
 *     "name" = {
 *       "id" = "text",
 *       "label" = @Translation("name"),
 *       "description" = @Translation("Name of the person or organization, i.e. [node:author:display-name]."),
 *     },
 *     "url" = {
 *       "id" = "url",
 *       "label" = @Translation("url"),
 *       "description" = @Translation("Absolute URL of the canonical Web page, like the URL of the author's profile page or the organization's official website, i.e. [node:author:url]."),
 *     },
 *     "sameAs" = {
 *       "id" = "url",
 *       "label" = @Translation("sameAs"),
 *       "description" = @Translation("Comma separated list of URLs for the person's or organization's official social media profile page(s)."),
 *     },
 *   },
 * )
 */
class Person extends PropertyTypeBase {

}
