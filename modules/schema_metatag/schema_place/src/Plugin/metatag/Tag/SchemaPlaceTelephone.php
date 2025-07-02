<?php

namespace Drupal\schema_place\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_place_telephone' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_place_telephone",
 *   label = @Translation("telephone"),
 *   description = @Translation("The telephone number of place."),
 *   name = "telephone",
 *   group = "schema_place",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaPlaceTelephone extends SchemaNameBase {

}
