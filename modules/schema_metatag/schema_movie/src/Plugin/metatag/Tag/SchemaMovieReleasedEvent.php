<?php

namespace Drupal\schema_movie\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_movie_released_event' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_movie_released_event",
 *   label = @Translation("releasedEvent"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Details about the original release of the work. Google expects only the country of the location."),
 *   name = "releasedEvent",
 *   group = "schema_movie",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "event",
 *   tree_parent = {
 *     "PublicationEvent",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaMovieReleasedEvent extends SchemaNameBase {

}
