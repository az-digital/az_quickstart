<?php

namespace Drupal\schema_movie\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Movie' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_movie",
 *   label = @Translation("Schema.org: Movie, Series, Season, Episode"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>, <a href="":url2"">:url2</a>, <a href="":url3"">:url3</a>, <a href="":url4"">:url4</a>. Also see <a href="":url5"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/Movie",
 *     ":url2" = "https://schema.org/Series",
 *     ":url3" = "https://schema.org/CreativeWorkSeason",
 *     ":url4" = "https://schema.org/Episode",
 *     ":url5" = "https://developers.google.com/search/docs/data-types/movie",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaMovie extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
