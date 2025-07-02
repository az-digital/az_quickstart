<?php

namespace Drupal\schema_movie\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_movie_duration' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_movie_duration",
 *   label = @Translation("duration"),
 *   description = @Translation("RECOMMENDED for Movie. Runtime of the work in ISO 8601 format (for example, 'PT2H22M' (142 minutes))."),
 *   name = "duration",
 *   group = "schema_movie",
 *   weight = 3,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "duration",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaMovieDuration extends SchemaNameBase {

}
