<?php

namespace Drupal\schema_movie\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_movie_producer' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_movie_producer",
 *   label = @Translation("producer"),
 *   description = @Translation("The producer of the work."),
 *   name = "producer",
 *   group = "schema_movie",
 *   weight = 15,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "organization",
 *   tree_parent = {
 *     "Person",
 *     "Organization",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaMovieProducer extends SchemaNameBase {

}
