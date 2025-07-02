<?php

namespace Drupal\schema_movie\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the '@id' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_movie_id",
 *   label = @Translation("@id"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Globally unique ID of the work in the form of a URL. The ID should be stable and not change over time. The URL is treated as an opaque string and does not have to be a working link."),
 *   name = "@id",
 *   group = "schema_movie",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaMovieId extends SchemaNameBase {

}
