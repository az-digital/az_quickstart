<?php

namespace Drupal\schema_movie\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'sameAs' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_movie_same_as",
 *   label = @Translation("sameAs"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. URL to a reference web page that unambiguously identifies the work. Example: IMDB, Wikipedia"),
 *   name = "sameAs",
 *   group = "schema_movie",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaMovieSameAs extends SchemaNameBase {

}
