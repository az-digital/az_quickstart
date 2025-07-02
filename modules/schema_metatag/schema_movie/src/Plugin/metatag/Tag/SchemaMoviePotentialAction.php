<?php

namespace Drupal\schema_movie\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_movie_potential_action' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_movie_potential_action",
 *   label = @Translation("potentialAction"),
 *   description = @Translation("REQUIRED BY GOOGLE. Potential action provided by this work."),
 *   name = "potentialAction",
 *   group = "schema_movie",
 *   weight = 11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "action",
 *   tree_parent = {
 *     "WatchAction",
 *     "ViewAction",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaMoviePotentialAction extends SchemaNameBase {

}
