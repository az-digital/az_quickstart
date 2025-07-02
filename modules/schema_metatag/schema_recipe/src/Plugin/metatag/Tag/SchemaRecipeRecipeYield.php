<?php

namespace Drupal\schema_recipe\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_recipe_recipe_yield' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_recipe_recipe_yield",
 *   label = @Translation("recipeYield"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The quantity produced by the recipe (for example, number of people served, number of servings, etc)."),
 *   name = "recipeYield",
 *   group = "schema_recipe",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaRecipeRecipeYield extends SchemaNameBase {

}
