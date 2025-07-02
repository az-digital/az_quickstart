<?php

namespace Drupal\schema_movie\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_movie_has_part' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_movie_has_part",
 *   label = @Translation("hasPart"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Short videos related to the Movie (use Clip), TVEpisode, TVSeries or TVSeason (use TVClip)."),
 *   name = "hasPart",
 *   group = "schema_movie",
 *   weight = 11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "clip",
 *   tree_parent = {
 *     "Clip",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMovieHasPart extends SchemaNameBase {

}
