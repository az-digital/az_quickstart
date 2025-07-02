<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Clip' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "clip",
 *   label = @Translation("Clip"),
 *   tree_parent = {
 *     "Clip",
 *   },
 *   tree_depth = 2,
 *   property_type = "Clip",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *       "tree_parent" = {},
 *       "tree_depth" = -1,
 *     },
 *     "description" = {
 *       "id" = "text",
 *       "label" = @Translation("description"),
 *       "description" = @Translation("One of the following values:\n'trailer': A preview or advertisement of the work.\n'behind_the_scenes': A summary of the production of the work.\n'highlight': A contiguous scene from the work."),
 *       "tree_parent" = {},
 *       "tree_depth" = -1,
 *     },
 *     "timeRequired" = {
 *       "id" = "duration",
 *       "label" = @Translation("timeRequired"),
 *       "description" = @Translation("Duration of the clip in ISO 8601 format, 'PT2M5S' (2min 5sec)."),
 *       "tree_parent" = {},
 *       "tree_depth" = -1,
 *     },
 *     "potentialAction" = {
 *       "id" = "action",
 *       "label" = @Translation("potentialAction"),
 *       "description" = @Translation("Potential action for the work."),
 *       "tree_parent" = {
 *         "WatchAction",
 *       },
 *       "tree_depth" = 0,
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
class Clip extends PropertyTypeBase {

}
