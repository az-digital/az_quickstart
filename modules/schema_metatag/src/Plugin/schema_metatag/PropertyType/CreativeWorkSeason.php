<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'CreativeWorkSeason' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "creative_work_season",
 *   label = @Translation("CreativeWorkSeason"),
 *   tree_parent = {
 *     "CreativeWorkSeason",
 *   },
 *   tree_depth = 2,
 *   property_type = "CreativeWorkSeason",
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
 *     "seasonNumber" = {
 *       "id" = "text",
 *       "label" = @Translation("seasonNumber"),
 *       "description" = @Translation("The number of the season."),
 *     },
 *   },
 * )
 */
class CreativeWorkSeason extends PropertyTypeBase {

}
