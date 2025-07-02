<?php

namespace Drupal\schema_recipe\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'recipeIngredients' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_recipe_recipe_ingredient",
 *   label = @Translation("recipeIngredient"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. A list of single ingredients used in the recipe, e.g. sugar, flour or garlic."),
 *   name = "recipeIngredient",
 *   group = "schema_recipe",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaRecipeRecipeIngredient extends SchemaNameBase {

}
