<?php

namespace Drupal\schema_movie\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_movie_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of work."),
 *   name = "@type",
 *   group = "schema_movie",
 *   weight = -5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "Movie",
 *     "Series",
 *     "CreativeWorkSeason",
 *     "Episode",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMovieType extends SchemaNameBase {

}
