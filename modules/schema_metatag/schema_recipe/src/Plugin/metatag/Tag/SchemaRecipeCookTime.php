<?php

namespace Drupal\schema_recipe\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'cookTime' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_recipe_cook_time",
 *   label = @Translation("cookTime"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Cook Time (The time it takes to actually cook the dish, in ISO 8601 duration format.)."),
 *   name = "cookTime",
 *   group = "schema_recipe",
 *   weight = 4,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "duration",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaRecipeCookTime extends SchemaNameBase {

}
