<?php

namespace Drupal\schema_movie\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'partOfSeries' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_movie_part_of_series",
 *   label = @Translation("partOfSeries"),
 *   description = @Translation("REQUIRED BY GOOGLE for TVEpisode, TVSeason."),
 *   name = "partOfSeries",
 *   group = "schema_movie",
 *   weight = 10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "creative_work",
 *   tree_parent = {
 *     "Series",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMoviePartOfSeries extends SchemaNameBase {

}
