<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for 'Organization' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "organization",
 *   label = @Translation("Organization"),
 *   tree_parent = {
 *     "Organization",
 *   },
 *   tree_depth = 2,
 *   property_type = "Organization",
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
 *     "address" = {
 *       "id" = "postal_address",
 *       "label" = @Translation("address"),
 *       "description" = @Translation("The address of the organization."),
 *       "tree_parent" = {
 *         "PostalAddress",
 *       },
 *       "tree_depth" = 0,
 *     },
 *     "logo" = {
 *       "id" = "image_object",
 *       "label" = @Translation("logo"),
 *       "description" = @Translation("The logo of the organization. For AMP pages, Google requires a image no larger than 600 x 60."),
 *       "tree_parent" = {
 *         "ImageObject",
 *       },
 *       "tree_depth" = 0,
 *     },
 *  },
 * )
 */
class Organization extends PropertyTypeBase {

}
